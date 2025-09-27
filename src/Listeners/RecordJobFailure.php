<?php

namespace houdaslassi\QueueMonitor\Listeners;

use houdaslassi\QueueMonitor\Notifications\JobFailedNotification;
use houdaslassi\QueueMonitor\Support\Traits\ExtractsRetryOf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;
use houdaslassi\QueueMonitor\Models\QueueJobRun;

class RecordJobFailure
{
    use ExtractsRetryOf;

    public function handle(JobFailed $event): void
    {
        $row = QueueJobRun::create([
            'uuid'             => (string) Str::uuid(),
            'job_class'        => method_exists($event->job, 'resolveName')
                ? $event->job->resolveName()
                : get_class($event->job),
            'queue'            => $event->job->getQueue(),
            'connection'       => $event->connectionName ?? null,
            'attempt'          => $event->job->attempts(),
            'status'           => 'failed',
            'exception_class'  => get_class($event->exception),
            'exception_message'=> Str::limit($event->exception->getMessage(), 2000),
            'stack'            => Str::limit($event->exception->getTraceAsString(), 4000),
            'finished_at'      => now(),
            'retried_from_id'  => $this->getRetryOf($event),
        ]);

        Log::info('$row record ',[
           $row
        ]);

        if (config('queue-monitor.notify.email') || config('queue-monitor.notify.slack_webhook')) {
            Notification::route('mail', config('queue-monitor.notify.email'))
                ->route('slack', config('queue-monitor.notify.slack_webhook'))
                ->notify(new JobFailedNotification($row));
        }
    }
}
