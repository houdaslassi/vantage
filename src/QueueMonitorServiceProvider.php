<?php

namespace houdaslassi\QueueMonitor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class QueueMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load our default config so config('queue-monitor') works
        $this->mergeConfigFrom(__DIR__.'/../config/queue-monitor.php', 'queue-monitor');
    }

    public function boot(): void
    {
        // publish the config file
        $this->publishes([
            __DIR__.'/../config/queue-monitor.php' => config_path('queue-monitor.php'),
        ], 'queue-monitor-config');

        // Load our migrations automatically
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Listen to Laravel's built-in queue events
        Event::listen(JobProcessing::class, [Listeners\RecordJobStart::class, 'handle']);
        Event::listen(JobProcessed::class,  [Listeners\RecordJobSuccess::class, 'handle']);
        Event::listen(JobFailed::class,     [Listeners\RecordJobFailure::class, 'handle']);
    }
}
