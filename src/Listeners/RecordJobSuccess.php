<?php

namespace houdaslassi\QueueMonitor\Listeners;

use houdaslassi\QueueMonitor\Support\Traits\ExtractsRetryOf;
use houdaslassi\QueueMonitor\Support\PayloadExtractor;
use Illuminate\Queue\Events\JobProcessed;
use houdaslassi\QueueMonitor\Models\QueueJobRun;
use Illuminate\Support\Facades\Log;

class RecordJobSuccess
{
    use ExtractsRetryOf;

    public function handle(JobProcessed $event): void
    {
        $uuid = $this->bestUuid($event);
        $jobClass = $this->jobClass($event);
        $queue = $event->job->getQueue();
        $connection = $event->connectionName ?? null;

        // Try to find by UUID first (most reliable)
        $row = QueueJobRun::where('uuid', $uuid)
            ->where('status', 'processing')
            ->first();

        // Fallback: try by job class, queue, connection (for recently created jobs)
        if (!$row) {
            $row = QueueJobRun::where('job_class', $jobClass)
                ->where('queue', $queue)
                ->where('connection', $connection)
                ->where('status', 'processing')
                ->where('created_at', '>', now()->subMinute()) // Only very recent
                ->orderByDesc('id')
                ->first();
        }

        if ($row) {
            // Update existing record
            $row->status = 'processed';
            $row->finished_at = now();
            if ($row->started_at) {
                $row->duration_ms = $row->finished_at->diffInMilliseconds($row->started_at);
            }
            $row->save();
            
            Log::debug('Queue Monitor: Job completed', [
                'id' => $row->id,
                'job_class' => $jobClass,
                'duration_ms' => $row->duration_ms,
            ]);
        } else {
            // Fallback: Create a new processed record if we didn't catch the start
            Log::warning('Queue Monitor: No processing record found, creating new', [
                'job_class' => $jobClass,
                'uuid' => $uuid,
            ]);
            
            QueueJobRun::create([
                'uuid' => $uuid,
                'job_class' => $jobClass,
                'queue' => $queue,
                'connection' => $connection,
                'attempt' => $event->job->attempts(),
                'status' => 'processed',
                'finished_at' => now(),
                'retried_from_id' => $this->getRetryOf($event),
                'payload' => PayloadExtractor::getPayload($event),
                'job_tags' => config('queue-monitor.tagging.enabled', true) 
                                ? PayloadExtractor::extractTags($event) 
                                : null,
            ]);
        }
    }
}
