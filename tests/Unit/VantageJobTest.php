<?php

use Illuminate\Support\Carbon;
use HoudaSlassi\Vantage\Enums\JobStatus;
use HoudaSlassi\Vantage\Models\VantageJob;
use Illuminate\Support\Str;

function makeJob(array $overrides = []): VantageJob
{
    return VantageJob::create(array_merge([
        'uuid' => (string) Str::uuid(),
        'job_class' => 'App\\Jobs\\ExampleJob',
        'queue' => 'default',
        'connection' => 'database',
        'status' => 'processing',
    ], $overrides));
}

it('can create a queue job run', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-uuid-123',
        'attempt' => 1,
        'started_at' => now(),
    ]);

    expect($vantageJob)->toBeInstanceOf(VantageJob::class)
        ->and($vantageJob->uuid)->toBe('test-uuid-123')
        ->and($vantageJob->job_class)->toBe('App\\Jobs\\ExampleJob')
        ->and($vantageJob->status)->toBe(JobStatus::Processing);
});

it('casts job_tags to array', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-uuid-tags',
        'job_tags' => ['tag1', 'tag2'],
    ]);

    expect($vantageJob->job_tags)->toBeArray()
        ->and($vantageJob->job_tags)->toHaveCount(2)
        ->and($vantageJob->job_tags)->toContain('tag1', 'tag2');
});

it('casts dates correctly', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-uuid-dates',
        'started_at' => '2024-01-01 10:00:00',
        'finished_at' => '2024-01-01 10:05:00',
    ]);

    expect($vantageJob->started_at)->toBeInstanceOf(Carbon::class)
        ->and($vantageJob->finished_at)->toBeInstanceOf(Carbon::class);
});

it('has retried_from relationship', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'parent-uuid',
        'status' => 'processed',
    ]);

    $retryJob = makeJob([
        'uuid' => 'retry-uuid',
        'retried_from_id' => $vantageJob->id,
    ]);

    expect($retryJob->retriedFrom)->toBeInstanceOf(VantageJob::class)
        ->and($retryJob->retriedFrom->id)->toBe($vantageJob->id);
});

it('has retries relationship', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'parent-uuid',
        'status' => 'processed',
    ]);

    $retry1 = makeJob([
        'uuid' => 'retry-1',
        'status' => 'processed',
        'retried_from_id' => $vantageJob->id,
    ]);

    $retry2 = makeJob([
        'uuid' => 'retry-2',
        'status' => 'failed',
        'retried_from_id' => $vantageJob->id,
    ]);

    $retries = $vantageJob->retries()->get();

    expect($retries)->toHaveCount(2)
        ->and($retries->pluck('id')->toArray())->toContain($retry1->id, $retry2->id);
});

it('checks if job has tag', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-uuid',
        'job_tags' => ['important', 'email', 'urgent'],
    ]);

    expect($vantageJob->hasTag('important'))->toBeTrue()
        ->and($vantageJob->hasTag('Important'))->toBeTrue() // Case insensitive
        ->and($vantageJob->hasTag('nonexistent'))->toBeFalse();
});

it('formats duration correctly', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-1',
        'status' => 'processed',
        'duration_ms' => 500,
    ]);

    $job2 = makeJob([
        'uuid' => 'test-2',
        'status' => 'processed',
        'duration_ms' => 2500,
    ]);

    $job3 = makeJob([
        'uuid' => 'test-3',
        'duration_ms' => null,
    ]);

    expect($vantageJob->formatted_duration)->toBe('500ms')
        ->and($job2->formatted_duration)->toBe('2.5s')
        ->and($job3->formatted_duration)->toBe('N/A');
});

it('filters by tag scope', function (): void {
    makeJob([
        'uuid' => 'test-1',
        'status' => 'processed',
        'job_tags' => ['email', 'important'],
    ]);

    makeJob([
        'uuid' => 'test-2',
        'status' => 'processed',
        'job_tags' => ['email'],
    ]);

    makeJob([
        'uuid' => 'test-3',
        'status' => 'processed',
        'job_tags' => ['important'],
    ]);

    $jobsWithEmail = VantageJob::withTag('email')->get();
    expect($jobsWithEmail)->toHaveCount(2);

    $jobsWithImportant = VantageJob::withTag('important')->get();
    expect($jobsWithImportant)->toHaveCount(2);
});

it('filters by status scope', function (): void {
    makeJob([
        'uuid' => 'test-1',
        'status' => 'processed',
    ]);

    makeJob([
        'uuid' => 'test-2',
        'status' => 'failed',
    ]);

    makeJob([
        'uuid' => 'test-3',
        'status' => 'processing',
    ]);

    expect(VantageJob::failed()->count())->toBe(1)
        ->and(VantageJob::successful()->count())->toBe(1)
        ->and(VantageJob::processing()->count())->toBe(1);
});

it('casts telemetry fields to integers', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-telemetry',
        'duration_ms' => '500',
        'memory_start_bytes' => '1048576',
        'memory_end_bytes' => '2097152',
        'memory_peak_start_bytes' => '1048576',
        'memory_peak_end_bytes' => '3145728',
        'memory_peak_delta_bytes' => '2097152',
        'cpu_user_ms' => '100',
        'cpu_sys_ms' => '50',
    ]);

    expect($vantageJob->duration_ms)->toBeInt()->toBe(500)
        ->and($vantageJob->memory_start_bytes)->toBeInt()->toBe(1048576)
        ->and($vantageJob->memory_end_bytes)->toBeInt()->toBe(2097152)
        ->and($vantageJob->memory_peak_start_bytes)->toBeInt()->toBe(1048576)
        ->and($vantageJob->memory_peak_end_bytes)->toBeInt()->toBe(3145728)
        ->and($vantageJob->memory_peak_delta_bytes)->toBeInt()->toBe(2097152)
        ->and($vantageJob->cpu_user_ms)->toBeInt()->toBe(100)
        ->and($vantageJob->cpu_sys_ms)->toBeInt()->toBe(50);
});

it('handles null telemetry fields', function (): void {
    $vantageJob = makeJob([
        'uuid' => 'test-null-telemetry',
        'duration_ms' => null,
        'memory_start_bytes' => null,
        'cpu_user_ms' => null,
    ]);

    expect($vantageJob->duration_ms)->toBeNull()
        ->and($vantageJob->memory_start_bytes)->toBeNull()
        ->and($vantageJob->cpu_user_ms)->toBeNull();
});

