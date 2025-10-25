<?php

namespace houdaslassi\Vantage\Listeners;

use houdaslassi\Vantage\Notifications\JobFailedNotification;
use houdaslassi\Vantage\Support\Traits\ExtractsRetryOf;
use houdaslassi\Vantage\Support\TagExtractor;
use houdaslassi\Vantage\Support\PayloadExtractor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;
use houdaslassi\Vantage\Models\QueueJobRun;

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
            'payload'          => PayloadExtractor::getPayload($event),
            'job_tags'         => TagExtractor::extract($event),
        ]);

        Log::info('Queue Monitor: Job failed', [
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
}

