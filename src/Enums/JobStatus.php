<?php

declare(strict_types=1);

namespace HoudaSlassi\Vantage\Enums;

/**
 * Job execution status
 *
 * Represents the lifecycle state of a queued job.
 */
enum JobStatus: string
{
    /**
     * Job is currently being processed by a worker
     */
    case Processing = 'processing';

    /**
     * Job completed successfully
     */
    case Processed = 'processed';

    /**
     * Job failed with an exception
     */
    case Failed = 'failed';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Processing',
            self::Processed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Processing => 'text-blue-600',
            self::Processed => 'text-green-600',
            self::Failed => 'text-red-600',
        };
    }

    /**
     * Check if this is a terminal state (job won't be processed again)
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Processing => false,
            self::Processed, self::Failed => true,
        };
    }

    /**
     * Check if this represents a successful execution
     */
    public function isSuccessful(): bool
    {
        return $this === self::Processed;
    }

    /**
     * Check if this represents a failure
     */
    public function isFailure(): bool
    {
        return $this === self::Failed;
    }

    /**
     * Get all terminal statuses
     *
     * @return array<JobStatus>
     */
    public static function terminalStatuses(): array
    {
        return [self::Processed, self::Failed];
    }

    /**
     * Create from string value (with validation)
     */
    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    /**
     * Try to create from string value (returns null if invalid)
     */
    public static function tryFromString(?string $value): ?self
    {
        return $value !== null ? self::tryFrom($value) : null;
    }
}
