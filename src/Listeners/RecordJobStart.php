<?php

namespace HoudaSlassi\Vantage\Listeners;

use Illuminate\Support\Str;
use HoudaSlassi\Vantage\Enums\JobStatus;
use HoudaSlassi\Vantage\Support\Traits\ExtractsRetryOf;
use HoudaSlassi\Vantage\Support\TagExtractor;
use HoudaSlassi\Vantage\Support\PayloadExtractor;
use HoudaSlassi\Vantage\Support\JobPerformanceContext;
use Illuminate\Queue\Events\JobProcessing;
use HoudaSlassi\Vantage\Models\VantageJob;
use Illuminate\Support\Facades\DB;

class RecordJobStart
{
    use ExtractsRetryOf;

    public function handle(JobProcessing $jobProcessing): void
    {
        // Master switch: if package is disabled, don't track anything
        if (!config('vantage.enabled', true)) {
            return;
        }

        $uuid = $this->bestUuid($jobProcessing);

        // Telemetry config & sampling
        $telemetryEnabled = config('vantage.telemetry.enabled', true);
        $sampleRate = (float) config('vantage.telemetry.sample_rate', 1.0);
        $captureCpu = config('vantage.telemetry.capture_cpu', true);

        $memoryStart = null;
        $memoryPeakStart = null;
        $cpuStart = null;

        if ($telemetryEnabled && (mt_rand() / mt_getrandmax()) <= $sampleRate) {
            $memoryStart = @memory_get_usage(true) ?: null;
            $memoryPeakStart = @memory_get_peak_usage(true) ?: null;

            if ($captureCpu && function_exists('getrusage')) {
                $ru = @getrusage();
                if (is_array($ru)) {
                    $userUs = ($ru['ru_utime.tv_sec'] ?? 0) * 1_000_000 + ($ru['ru_utime.tv_usec'] ?? 0);
                    $sysUs  = ($ru['ru_stime.tv_sec'] ?? 0) * 1_000_000 + ($ru['ru_stime.tv_usec'] ?? 0);
                    $cpuStart = ['user_us' => $userUs, 'sys_us' => $sysUs];
                }
            }

            // keep CPU baseline in memory only
            if ($cpuStart) {
                JobPerformanceContext::setBaseline($uuid, [
                    'cpu_start_user_us' => $cpuStart['user_us'],
                    'cpu_start_sys_us' => $cpuStart['sys_us'],
                ]);
            }
        }

        // Use transaction to ensure atomic operations
        DB::transaction(function () use ($jobProcessing, $uuid, $memoryStart, $memoryPeakStart): void {
            $payloadJson = PayloadExtractor::getPayload($jobProcessing);
            $jobClass = $this->jobClass($jobProcessing);
            $queue = $jobProcessing->job->getQueue();
            $connection = $jobProcessing->connectionName ?? null;

            // Always create a new record on job start
            // The UUID will be used by Success/Failure listeners to find and update this record
            VantageJob::create([
                'uuid'             => $uuid,
                'job_class'        => $jobClass,
                'queue'            => $queue,
                'connection'       => $connection,
                'attempt'          => $jobProcessing->job->attempts(),
                'status'           => JobStatus::Processing,
                'started_at'       => now(),
                'retried_from_id'  => $this->getRetryOf($jobProcessing),
                'payload'          => $payloadJson,
                'job_tags'         => TagExtractor::extract($jobProcessing),
                // telemetry columns (nullable if disabled/unsampled)
                'memory_start_bytes' => $memoryStart,
                'memory_peak_start_bytes' => $memoryPeakStart,
            ]);
        });
    }

    /**
     * Get best available UUID for the job
     */
    protected function bestUuid(JobProcessing $jobProcessing): string
    {
        // Try Laravel's built-in UUID
        if (method_exists($jobProcessing->job, 'uuid') && $jobProcessing->job->uuid()) {
            return (string) $jobProcessing->job->uuid();
        }

        // Fallback to job ID
        if (method_exists($jobProcessing->job, 'getJobId') && $jobProcessing->job->getJobId()) {
            return (string) $jobProcessing->job->getJobId();
        }

        // Last resort: generate new UUID
        return (string) Str::uuid();
    }

    /**
     * Get job class name
     */
    protected function jobClass(JobProcessing $jobProcessing): string
    {
        if (method_exists($jobProcessing->job, 'resolveName')) {
            return $jobProcessing->job->resolveName();
        }

        return $jobProcessing->job::class;
    }
}

