<?php

namespace HoudaSlassi\Vantage\Listeners;

use HoudaSlassi\Vantage\Enums\JobStatus;
use HoudaSlassi\Vantage\Notifications\JobFailedNotification;
use HoudaSlassi\Vantage\Support\Traits\ExtractsRetryOf;
use HoudaSlassi\Vantage\Support\TagExtractor;
use HoudaSlassi\Vantage\Support\PayloadExtractor;
use HoudaSlassi\Vantage\Support\JobPerformanceContext;
use HoudaSlassi\Vantage\Support\VantageLogger;
use Illuminate\Support\Facades\Notification;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;
use HoudaSlassi\Vantage\Models\VantageJob;
use Illuminate\Support\Facades\DB;

class RecordJobFailure
{
    use ExtractsRetryOf;

    public function handle(JobFailed $jobFailed): void
    {
        // Master switch: if package is disabled, don't track anything
        if (!config('vantage.enabled', true)) {
            return;
        }

        $uuid = $this->bestUuid($jobFailed);

        try {
            $this->recordJobFailureDetails($jobFailed, $uuid);
        } finally {
            // Always clear baseline to prevent memory leaks, even if an exception occurs
            JobPerformanceContext::clearBaseline($uuid);
        }
    }

    protected function recordJobFailureDetails(JobFailed $jobFailed, string $uuid): void
    {
        $jobClass = $this->jobClass($jobFailed);
        $queue = $jobFailed->job->getQueue();
        $connection = $jobFailed->connectionName ?? null;

        // Use transaction for atomic database operations
        $row = DB::transaction(function () use ($jobFailed, $uuid, $jobClass, $queue, $connection) {
            // Try to find existing processing record
            $row = null;

            // First, try by stable UUID if available (most reliable)
            $hasStableUuid = (method_exists($jobFailed->job, 'uuid') && $jobFailed->job->uuid())
                          || (method_exists($jobFailed->job, 'getJobId') && $jobFailed->job->getJobId());

            if ($hasStableUuid) {
                $row = VantageJob::where('uuid', $uuid)
                    ->where('status', JobStatus::Processing)
                    ->first();
            }

            // Fallback: try by job class, queue, connection (ONLY if UUID not available)
            // This should rarely be needed since Laravel 8+ provides uuid()
            if (!$row && !$hasStableUuid) {
                $row = VantageJob::where('job_class', $jobClass)
                    ->where('queue', $queue)
                    ->where('connection', $connection)
                    ->where('status', JobStatus::Processing)
                    ->where('created_at', '>', now()->subMinute()) // Keep it tight to avoid matching wrong job
                    ->orderByDesc('id')
                    ->first();
            }

        $telemetryEnabled = config('vantage.telemetry.enabled', true);
        $captureCpu = config('vantage.telemetry.capture_cpu', true);

        $memoryEnd = null;
        $memoryPeakEnd = null;
        $cpuDelta = ['user_ms' => null, 'sys_ms' => null];

        if ($telemetryEnabled) {
            $memoryEnd = @memory_get_usage(true) ?: null;
            $memoryPeakEnd = @memory_get_peak_usage(true) ?: null;

            if ($captureCpu && function_exists('getrusage')) {
                $ru = @getrusage();
                if (is_array($ru)) {
                    $userUs = ($ru['ru_utime.tv_sec'] ?? 0) * 1_000_000 + ($ru['ru_utime.tv_usec'] ?? 0);
                    $sysUs  = ($ru['ru_stime.tv_sec'] ?? 0) * 1_000_000 + ($ru['ru_stime.tv_usec'] ?? 0);
                    if ($uuid !== '' && $uuid !== '0') {
                        $baseline = JobPerformanceContext::getBaseline($uuid);
                        if ($baseline) {
                            $cpuDelta['user_ms'] = max(0, (int) round(($userUs - ($baseline['cpu_start_user_us'] ?? 0)) / 1000));
                            $cpuDelta['sys_ms']  = max(0, (int) round(($sysUs  - ($baseline['cpu_start_sys_us'] ?? 0)) / 1000));
                        }
                    }
                }
            }
        }

        if ($row) {
            // Update existing processing record
            $row->status = JobStatus::Failed;
            $row->exception_class = $jobFailed->exception::class;
            $row->exception_message = Str::limit($jobFailed->exception->getMessage(), 2000);
            $row->stack = Str::limit($jobFailed->exception->getTraceAsString(), 4000);
            $row->finished_at = now();
            if ($row->started_at) {
                $duration = $row->finished_at->diffInRealMilliseconds($row->started_at, true);
                $row->duration_ms = max(0, (int) $duration);
            }

            // Telemetry end metrics
            $row->memory_end_bytes = $memoryEnd;
            $row->memory_peak_end_bytes = $memoryPeakEnd;
            if ($row->memory_peak_start_bytes !== null && $memoryPeakEnd !== null) {
                $row->memory_peak_delta_bytes = max(0, (int) ($memoryPeakEnd - $row->memory_peak_start_bytes));
            }

            $row->cpu_user_ms = $cpuDelta['user_ms'];
            $row->cpu_sys_ms = $cpuDelta['sys_ms'];
            $row->save();
        } else {
            // Fallback: Create new record if we didn't catch the start
            VantageLogger::warning('Queue Monitor: No processing record found for failed job, creating new', [
                'job_class' => $jobClass,
                'uuid' => $uuid,
            ]);

            $row = VantageJob::create([
                'uuid'             => $uuid,
                'job_class'        => $jobClass,
                'queue'            => $queue,
                'connection'       => $connection,
                'attempt'          => $jobFailed->job->attempts(),
                'status'           => JobStatus::Failed,
                'exception_class'  => $jobFailed->exception::class,
                'exception_message'=> Str::limit($jobFailed->exception->getMessage(), 2000),
                'stack'            => Str::limit($jobFailed->exception->getTraceAsString(), 4000),
                'finished_at'      => now(),
                'retried_from_id'  => $this->getRetryOf($jobFailed),
                'payload'          => PayloadExtractor::getPayload($jobFailed, true), // Force extraction on failure
                'job_tags'         => TagExtractor::extract($jobFailed),
                // telemetry end metrics
                'memory_end_bytes' => $memoryEnd,
                'memory_peak_end_bytes' => $memoryPeakEnd,
                'cpu_user_ms' => $cpuDelta['user_ms'],
                'cpu_sys_ms' => $cpuDelta['sys_ms'],
            ]);
            }

            return $row;
        });

        VantageLogger::info('Queue Monitor: Job failed', [
           'id' => $row->id,
           'job_class' => $row->job_class,
           'exception' => $row->exception_class,
        ]);

        if (config('vantage.notify.email') || config('vantage.notify.slack_webhook')) {
            Notification::route('mail', config('vantage.notify.email'))
                ->route('slack', config('vantage.notify.slack_webhook'))
                ->notify(new JobFailedNotification($row));
        }
    }

    /**
     * Get best available UUID for the job
     */
    protected function bestUuid(JobFailed $jobFailed): string
    {
        // Try Laravel's built-in UUID
        if (method_exists($jobFailed->job, 'uuid') && $jobFailed->job->uuid()) {
            return (string) $jobFailed->job->uuid();
        }

        // Fallback to job ID
        if (method_exists($jobFailed->job, 'getJobId') && $jobFailed->job->getJobId()) {
            return (string) $jobFailed->job->getJobId();
        }

        // Last resort: generate new UUID
        return (string) Str::uuid();
    }

    /**
     * Get job class name
     */
    protected function jobClass(JobFailed $jobFailed): string
    {
        if (method_exists($jobFailed->job, 'resolveName')) {
            return $jobFailed->job->resolveName();
        }

        return $jobFailed->job::class;
    }
}

