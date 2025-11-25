<?php

use HoudaSlassi\Vantage\Models\VantageJob;
use HoudaSlassi\Vantage\Support\QueueDepthChecker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('jobs');
});

it('returns queue depths with metadata for the database driver', function (): void {
    config()->set('queue.default', 'database');
    config()->set('queue.connections.database', [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ]);

    Schema::create('jobs', function (Blueprint $blueprint): void {
        $blueprint->id();
        $blueprint->string('queue')->default('default');
        $blueprint->longText('payload')->nullable();
        $blueprint->unsignedTinyInteger('attempts')->default(0);
        $blueprint->unsignedInteger('reserved_at')->nullable();
        $blueprint->unsignedInteger('available_at')->nullable();
        $blueprint->unsignedInteger('created_at')->nullable();
    });

    $counts = [
        'default' => 2,
        'emails' => 150,
        'critical-queue' => 1200,
    ];

    foreach ($counts as $queue => $count) {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'queue' => $queue,
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ];
        }

        DB::table('jobs')->insert($rows);
    }

    $depths = QueueDepthChecker::getQueueDepthWithMetadata();

    expect($depths)->toHaveKeys(['default', 'emails', 'critical-queue'])
        ->and($depths['default']['depth'])->toBe(2)
        ->and($depths['default']['status'])->toBe('normal')
        ->and($depths['emails']['status'])->toBe('warning')
        ->and($depths['critical-queue']['status'])->toBe('critical')
        ->and($depths['default']['driver'])->toBe('database');

    expect(QueueDepthChecker::getTotalQueueDepth())->toBe(array_sum($counts));
});

it('returns a default queue entry when no jobs are present', function (): void {
    config()->set('queue.default', 'database');
    config()->set('queue.connections.database', [
        'driver' => 'database',
        'table' => 'jobs',
    ]);

    Schema::create('jobs', function (Blueprint $blueprint): void {
        $blueprint->id();
        $blueprint->string('queue')->default('default');
        $blueprint->longText('payload')->nullable();
        $blueprint->unsignedTinyInteger('attempts')->default(0);
        $blueprint->unsignedInteger('reserved_at')->nullable();
        $blueprint->unsignedInteger('available_at')->nullable();
        $blueprint->unsignedInteger('created_at')->nullable();
    });

    $depths = QueueDepthChecker::getQueueDepthWithMetadataAlways();

    expect($depths)->toHaveKey('default')
        ->and($depths['default']['depth'])->toBe(0)
        ->and($depths['default']['status'])->toBe('healthy')
        ->and($depths['default']['driver'])->toBe('database');
});

it('falls back to processing jobs when the driver is unsupported', function (): void {
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.driver', 'sync');

    VantageJob::create([
        'uuid' => 'processing-job',
        'job_class' => 'App\\Jobs\\ExampleJob',
        'queue' => 'reports',
        'connection' => 'sync',
        'status' => 'processing',
    ]);

    $depths = QueueDepthChecker::getQueueDepth('reports');

    expect($depths)->toBe(['reports' => 1])
        ->and(QueueDepthChecker::getTotalQueueDepth())->toBe(1);
});

