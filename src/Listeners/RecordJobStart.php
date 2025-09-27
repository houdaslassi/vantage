<?php

namespace houdaslassi\QueueMonitor\Listeners;

use houdaslassi\QueueMonitor\Support\Traits\ExtractsRetryOf;
use Illuminate\Queue\Events\JobProcessing;
use houdaslassi\QueueMonitor\Models\QueueJobRun;

class RecordJobStart
{
    use ExtractsRetryOf;

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
            'retried_from_id'  => $this->getRetryOf($event),
        ]);
    }
}
