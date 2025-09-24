<?php

namespace houdaslassi\QueueMonitor\Listeners;

use Illuminate\Queue\Events\JobProcessing;
use houdaslassi\QueueMonitor\Models\QueueJobRun;

class RecordJobStart
{
    public function handle(JobProcessing $event): void
    {
        QueueJobRun::create([
            'uuid'        => $this->bestUuid($event),
            'job_class'   => $this->jobClass($event),
            'queue'       => $event->job->getQueue(),
            'connection'  => $event->connectionName ?? null,
            'attempt'     => $event->job->attempts(),
            'status'      => 'processing',
            'started_at'  => now(),
        ]);
    }

    protected function jobClass(JobProcessing $event): string
    {
        return method_exists($event->job, 'resolveName')
            ? $event->job->resolveName()
            : get_class($event->job);
    }

    protected function bestUuid(JobProcessing $event): string
    {
        // Prefer a stable id if available (Laravel versions differ here)
        if (method_exists($event->job, 'uuid') && $event->job->uuid()) {
            return (string) $event->job->uuid();
        }
        if (method_exists($event->job, 'getJobId') && $event->job->getJobId()) {
            return (string) $event->job->getJobId();
        }
        // Otherwise we’ll generate a UUID here; success listener will match by class/queue/connection
        return (string) \Illuminate\Support\Str::uuid();
    }
}
