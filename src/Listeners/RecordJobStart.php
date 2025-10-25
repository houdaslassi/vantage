<?php

namespace houdaslassi\QueueMonitor\Listeners;

use houdaslassi\QueueMonitor\Support\Traits\ExtractsRetryOf;
use houdaslassi\QueueMonitor\Support\TagExtractor;
use houdaslassi\QueueMonitor\Support\PayloadExtractor;
use Illuminate\Queue\Events\JobProcessing;
use houdaslassi\QueueMonitor\Models\QueueJobRun;

class RecordJobStart
{
    use ExtractsRetryOf;

    public function handle(JobProcessing $event): void
    {
        QueueJobRun::create([
            'uuid'             => $this->bestUuid($event),
            'job_class'        => $this->jobClass($event),
            'queue'            => $event->job->getQueue(),
            'connection'       => $event->connectionName ?? null,
            'attempt'          => $event->job->attempts(),
            'status'           => 'processing',
            'started_at'       => now(),
            'retried_from_id'  => $this->getRetryOf($event),
            'payload'          => PayloadExtractor::getPayload($event),
            'job_tags'         => TagExtractor::extract($event),
        ]);
    }

    /**
     * Get best available UUID for the job
     */
    protected function bestUuid(JobProcessing $event): string
    {
        // Try Laravel's built-in UUID
        if (method_exists($event->job, 'uuid') && $event->job->uuid()) {
            return (string) $event->job->uuid();
        }

        // Fallback to job ID
        if (method_exists($event->job, 'getJobId') && $event->job->getJobId()) {
            return (string) $event->job->getJobId();
        }

        // Last resort: generate new UUID
        return (string) \Illuminate\Support\Str::uuid();
    }

    /**
     * Get job class name
     */
    protected function jobClass(JobProcessing $event): string
    {
        if (method_exists($event->job, 'resolveName')) {
            return $event->job->resolveName();
        }

        return get_class($event->job);
    }
}

