<?php

namespace houdaslassi\QueueMonitor\Listeners;

use houdaslassi\QueueMonitor\Support\Traits\ExtractsRetryOf;
use Illuminate\Queue\Events\JobProcessed;
use houdaslassi\QueueMonitor\Models\QueueJobRun;

class RecordJobSuccess
{
    use ExtractsRetryOf;

    public function handle(JobProcessed $event): void
    {
        $jobClass = $this->getJobClass($event);
        $queue = $event->job->getQueue();
        $connection = $event->connectionName ?? null;

        // Try to find the most recent "processing" record for this job context
        $row = QueueJobRun::where('job_class', $jobClass)
            ->where('queue', $queue)
            ->where('connection', $connection)
            ->where('status', 'processing')
            ->orderByDesc('id')
            ->first();

        if ($row) {
            $row->status = 'processed';
            $row->finished_at = now();
            if ($row->started_at) {
                $row->duration_ms = $row->finished_at->diffInMilliseconds($row->started_at);
            }
            $row->save();
            return;
        }

        // Fallback: if we didn't catch the start event, create a processed row
        QueueJobRun::create([
            'uuid' => $this->getBestUuid($event),
            'job_class' => $jobClass,
            'queue' => $queue,
            'connection' => $connection,
            'attempt' => $event->job->attempts(),
            'status' => 'processed',
            'finished_at' => now(),
            'retried_from_id' => $this->getRetryOf($event), // Added retry tracking
        ]);
    }
}
