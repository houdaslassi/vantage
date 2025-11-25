<?php

declare(strict_types=1);

namespace HoudaSlassi\Vantage\Enums;

/**
 * Payload extraction strategy
 *
 * Determines when job payloads should be extracted and stored.
 */
enum PayloadStrategy: string
{
    /**
     * Extract payload for every job (higher overhead, complete debugging info)
     */
    case Always = 'always';

    /**
     * Only extract payload when job fails (recommended for production)
     */
    case OnFailure = 'on_failure';

    /**
     * Never extract or store payload (minimal overhead, no retry support)
     */
    case Never = 'never';

    /**
     * Get human-readable description
     */
    public function description(): string
    {
        return match ($this) {
            self::Always => 'Extract payload for every job',
            self::OnFailure => 'Extract payload only on failure',
            self::Never => 'Never extract payload',
        };
    }

    /**
     * Check if payload should be extracted on job start
     */
    public function shouldExtractOnStart(): bool
    {
        return $this === self::Always;
    }

    /**
     * Check if payload should be extracted on job failure
     */
    public function shouldExtractOnFailure(): bool
    {
        return match ($this) {
            self::Always, self::OnFailure => true,
            self::Never => false,
        };
    }

    /**
     * Check if retry is supported with this strategy
     */
    public function supportsRetry(): bool
    {
        return $this !== self::Never;
    }

    /**
     * Create from string with fallback to default
     */
    public static function fromStringOrDefault(?string $value): self
    {
        if ($value === null) {
            return self::OnFailure;
        }

        return self::tryFrom($value) ?? self::OnFailure;
    }
}
