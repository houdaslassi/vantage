<?php

use HoudaSlassi\Vantage\Enums\JobStatus;
use HoudaSlassi\Vantage\Listeners\RecordJobSuccess;
use HoudaSlassi\Vantage\Models\VantageJob;
use Illuminate\Queue\Events\JobProcessed;

it('skips counting released jobs as processed', function (): void {
    VantageJob::query()->delete();

    $releasedJob = new class {
        public function getQueue(): string { return 'default'; }

        public function attempts(): int { return 1; }

        public function uuid(): string { return 'released-uuid'; }

        public function resolveName(): string { return 'App\\Jobs\\RateLimitedJob'; }

        public function isReleased(): bool { return true; }

        public function isDeletedOrReleased(): bool { return true; }
    };

    $event = new JobProcessed('database', $releasedJob);
    (new RecordJobSuccess())->handle($event);

    expect(VantageJob::where('uuid', 'released-uuid')->exists())->toBeFalse();
});

it('still counts normal processed jobs', function (): void {
    VantageJob::query()->delete();

    $record = VantageJob::create([
        'uuid' => 'normal-uuid',
        'job_class' => 'App\\Jobs\\NormalJob',
        'status' => 'processing',
        'started_at' => now()->subSecond(),
    ]);

    $normalJob = new class {
        public function getQueue(): string { return 'default'; }

        public function attempts(): int { return 1; }

        public function uuid(): string { return 'normal-uuid'; }

        public function resolveName(): string { return 'App\\Jobs\\NormalJob'; }

        public function isReleased(): bool { return false; }

        public function isDeletedOrReleased(): bool { return false; }
    };

    $event = new JobProcessed('database', $normalJob);
    (new RecordJobSuccess())->handle($event);

    $updated = VantageJob::find($record->id);

    expect($updated->status)->toBe(JobStatus::Processed)
        ->and($updated->finished_at)->not()->toBeNull();
});

