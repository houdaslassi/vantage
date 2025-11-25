<?php

namespace HoudaSlassi\Vantage\Listeners;

use HoudaSlassi\Vantage\Enums\JobStatus;
use HoudaSlassi\Vantage\Support\Traits\ExtractsRetryOf;
use HoudaSlassi\Vantage\Support\TagExtractor;
use HoudaSlassi\Vantage\Support\PayloadExtractor;
use HoudaSlassi\Vantage\Support\JobPerformanceContext;
use Illuminate\Queue\Events\JobProcessed;
use HoudaSlassi\Vantage\Models\VantageJob;
use HoudaSlassi\Vantage\Support\VantageLogger;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RecordJobSuccess
{
    use ExtractsRetryOf;

    public function handle(JobProcessed $jobProcessed): void
    {
        // Master switch: if package is disabled, don't track anything
        if (!config('vantage.enabled', true)) {
            return;
        }

        // Some jobs (like rate-limited ones) are "processed" only to be released immediately.
        // Laravel exposes helpers to detect this so we don't count them as successful runs.
        if (method_exists($jobProcessed->job, 'isReleased') && $jobProcessed->job->isReleased()) {
            VantageLogger::debug('Queue Monitor: Job was released, skipping processed record', [
                'job_class' => $this->jobClass($jobProcessed),
            ]);
            return;
        }

        if (method_exists($jobProcessed->job, 'isDeletedOrReleased') && $jobProcessed->job->isDeletedOrReleased()) {
            VantageLogger::debug('Queue Monitor: Job was deleted or released, skipping processed record', [
                'job_class' => $this->jobClass($jobProcessed),
            ]);
            return;
        }

        $uuid = $this->bestUuid($jobProcessed);

        try {
            $this->recordJobCompletion($jobProcessed, $uuid);
        } finally {
            // Always clear baseline to prevent memory leaks, even if an exception occurs
            JobPerformanceContext::clearBaseline($uuid);
        }
    }

    protected function recordJobCompletion(JobProcessed $jobProcessed, string $uuid): void
    {
        $jobClass = $this->jobClass($jobProcessed);
        $queue = $jobProcessed->job->getQueue();
        $connection = $jobProcessed->connectionName ?? null;

        DB::transaction(function () use ($jobProcessed, $uuid, $jobClass, $queue, $connection): void {
            $row = null;

            // Try by stable UUID if available (most reliable)
            $hasStableUuid = (method_exists($jobProcessed->job, 'uuid') && $jobProcessed->job->uuid())
                          || (method_exists($jobProcessed->job, 'getJobId') && $jobProcessed->job->getJobId());

            if ($hasStableUuid) {
                $row = VantageJob::query()->where('uuid', $uuid)
                    ->where('status', JobStatus::Processing)
                    ->first();
            }

            // Fallback: try by job class, queue, connection (ONLY if UUID not available)
            // This should rarely be needed since Laravel 8+ provides uuid()
            if (!$row && !$hasStableUuid) {
                $row = VantageJob::query()->where('job_class', $jobClass)
                    ->where('queue', $queue)
                    ->where('connection', $connection)
                    ->where('status', JobStatus::Processing)
                    ->where('created_at', '>', now()->subMinute()) // Keep it tight to avoid matching wrong job
                    ->orderByDesc('id')
                    ->first();
            }

        if ($row) {
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
                        $baseline = JobPerformanceContext::getBaseline($uuid);
                        if ($baseline) {
                            $cpuDelta['user_ms'] = max(0, (int) round(($userUs - ($baseline['cpu_start_user_us'] ?? 0)) / 1000));
                            $cpuDelta['sys_ms']  = max(0, (int) round(($sysUs  - ($baseline['cpu_start_sys_us'] ?? 0)) / 1000));
                        }
                    }
                }
            }

            $row->status = JobStatus::Processed;
            $row->finished_at = now();
            if ($row->started_at) {
                $duration = $row->finished_at->diffInRealMilliseconds($row->started_at, true);
                $row->duration_ms = max(0, (int) $duration);
            }

            $row->memory_end_bytes = $memoryEnd;
            $row->memory_peak_end_bytes = $memoryPeakEnd;
            if ($row->memory_peak_start_bytes !== null && $memoryPeakEnd !== null) {
                $row->memory_peak_delta_bytes = max(0, (int) ($memoryPeakEnd - $row->memory_peak_start_bytes));
            }

            $row->cpu_user_ms = $cpuDelta['user_ms'];
            $row->cpu_sys_ms = $cpuDelta['sys_ms'];

            $row->save();

            VantageLogger::debug('Queue Monitor: Job completed', [
                'id' => $row->id,
                'job_class' => $jobClass,
                'duration_ms' => $row->duration_ms,
            ]);
        } else {
            // Fallback: Create a new processed record if we didn't catch the start
            VantageLogger::warning('Queue Monitor: No processing record found, creating new', [
                'job_class' => $jobClass,
                'uuid' => $uuid,
            ]);

                VantageJob::create([
                    'uuid' => $uuid,
                    'job_class' => $jobClass,
                    'queue' => $queue,
                    'connection' => $connection,
                    'attempt' => $jobProcessed->job->attempts(),
                    'status' => JobStatus::Processed,
                    'finished_at' => now(),
                    'retried_from_id' => $this->getRetryOf($jobProcessed),
                    'payload' => PayloadExtractor::getPayload($jobProcessed),
                    'job_tags' => TagExtractor::extract($jobProcessed),
                ]);
            }
        });
    }

    protected function bestUuid(JobProcessed $jobProcessed): string
    {
        if (method_exists($jobProcessed->job, 'uuid') && $jobProcessed->job->uuid()) {
            return (string) $jobProcessed->job->uuid();
        }

        if (method_exists($jobProcessed->job, 'getJobId') && $jobProcessed->job->getJobId()) {
            return (string) $jobProcessed->job->getJobId();
        }

        return (string) Str::uuid();
    }


    protected function jobClass(JobProcessed $jobProcessed): string
    {
        if (method_exists($jobProcessed->job, 'resolveName')) {
            return $jobProcessed->job->resolveName();
        }

        return $jobProcessed->job::class;
    }
}
