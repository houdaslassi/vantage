<?php

namespace houdaslassi\Vantage;

use houdaslassi\Vantage\Console\Commands\RetryFailedJob;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class VantageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load our default config so config('vantage') works
        $this->mergeConfigFrom(__DIR__.'/../config/vantage.php', 'vantage');
    }

    public function boot(): void
    {
        // publish the config file
        $this->publishes([
            __DIR__.'/../config/queue-monitor.php' => config_path('queue-monitor.php'),
        ], 'queue-monitor-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\RetryFailedJob::class,
                Console\Commands\CleanupStuckJobs::class,
                Console\Commands\ListJobs::class,
                Console\Commands\TagStats::class,
            ]);
        }

        // Load our migrations automatically
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/vantage-views', 'vantage');

        // Load routes if enabled
        if (config('vantage.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // Listen to Laravel's built-in queue events
        Event::listen(JobProcessing::class, [Listeners\RecordJobStart::class, 'handle']);
        Event::listen(JobProcessed::class,  [Listeners\RecordJobSuccess::class, 'handle']);
        Event::listen(JobFailed::class,     [Listeners\RecordJobFailure::class, 'handle']);
    }
}
