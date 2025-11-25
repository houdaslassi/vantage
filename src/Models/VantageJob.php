<?php

declare(strict_types=1);

namespace HoudaSlassi\Vantage\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use HoudaSlassi\Vantage\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vantage Job Model
 *
 * Tracks the execution lifecycle of queued jobs including timing,
 * performance metrics, failures, and retry chains.
 *
 * @property int $id
 * @property string $uuid
 * @property string $job_class
 * @property string|null $queue
 * @property string|null $connection
 * @property int $attempt
 * @property JobStatus $status
 * @property int|null $duration_ms
 * @property string|null $exception_class
 * @property string|null $exception_message
 * @property string|null $stack
 * @property array|null $payload
 * @property array|null $job_tags
 * @property int|null $retried_from_id
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int|null $memory_start_bytes
 * @property int|null $memory_end_bytes
 * @property int|null $memory_peak_start_bytes
 * @property int|null $memory_peak_end_bytes
 * @property int|null $memory_peak_delta_bytes
 * @property int|null $cpu_user_ms
 * @property int|null $cpu_sys_ms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VantageJob extends Model
{
    // Byte conversion constants
    private const BYTES_PER_KB = 1024;

    private const BYTES_PER_MB = 1024 * 1024;

    private const BYTES_PER_GB = 1024 * 1024 * 1024;

    // Time conversion constants
    private const MS_PER_SECOND = 1000;

    protected $table = 'vantage_jobs';

    protected static $unguarded = true;

    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('vantage.database_connection') ?? parent::getConnectionName();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
            'job_tags'    => 'array',
            'payload'     => 'array',
            'status'      => JobStatus::class,
            // Telemetry numeric casts
            'duration_ms' => 'integer',
            'memory_start_bytes' => 'integer',
            'memory_end_bytes' => 'integer',
            'memory_peak_start_bytes' => 'integer',
            'memory_peak_end_bytes' => 'integer',
            'memory_peak_delta_bytes' => 'integer',
            'cpu_user_ms' => 'integer',
            'cpu_sys_ms' => 'integer',
        ];
    }

    /**
     * Get the job that this was retried from
     *
     * @return BelongsTo<VantageJob, VantageJob>
     */
    public function retriedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retried_from_id');
    }

    /**
     * Get all retry attempts of this job
     *
     * @return HasMany<VantageJob>
     */
    public function retries(): HasMany
    {
        return $this->hasMany(self::class, 'retried_from_id');
    }

    /**
     * Get payload as decoded array
     *
     * @return array<string, mixed>|null
     */
    public function getDecodedPayloadAttribute(): ?array
    {
        if (!$this->payload) {
            return null;
        }

        return json_decode($this->payload, true);
    }

    /**
     * Scope: Filter by tag
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeWithTag($query, string $tag): void
    {
        $query->whereJsonContains('job_tags', strtolower($tag));
    }

    /**
     * Scope: Filter by any of multiple tags
     *
     * @param Builder<VantageJob> $query
     * @param array<string> $tags
     */
    public function scopeWithAnyTag($query, array $tags): void
    {
        $query->where(function($q) use ($tags): void {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('job_tags', strtolower($tag));
            }
        });
    }

    /**
     * Scope: Filter by all tags (must have all)
     *
     * @param Builder<VantageJob> $query
     * @param array<string> $tags
     */
    public function scopeWithAllTags($query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('job_tags', strtolower($tag));
        }
    }

    /**
     * Scope: Exclude jobs with specific tag
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeWithoutTag($query, string $tag): void
    {
        $query->where(function($q) use ($tag): void {
            $q->whereNull('job_tags')
              ->orWhereJsonDoesntContain('job_tags', strtolower($tag));
        });
    }

    /**
     * Scope: Filter by job class
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeOfClass($query, string $class): void
    {
        $query->where('job_class', $class);
    }

    /**
     * Scope: Filter by status
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeWithStatus($query, JobStatus|string $status): void
    {
        $statusValue = $status instanceof JobStatus ? $status->value : $status;
        $query->where('status', $statusValue);
    }

    /**
     * Scope: Failed jobs only
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeFailed($query): void
    {
        $query->where('status', JobStatus::Failed);
    }

    /**
     * Scope: Successful jobs only
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeSuccessful($query): void
    {
        $query->where('status', JobStatus::Processed);
    }

    /**
     * Scope: Processing jobs only
     *
     * @param Builder<VantageJob> $query
     */
    public function scopeProcessing($query): void
    {
        $query->where('status', JobStatus::Processing);
    }

    /**
     * Check if job has specific tag
     */
    public function hasTag(string $tag): bool
    {
        if (!$this->job_tags) {
            return false;
        }

        return in_array(strtolower($tag), array_map(strtolower(...), $this->job_tags));
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        return $this->formatMilliseconds($this->duration_ms);
    }

    /**
     * Format bytes to human-readable format (B, KB, MB, GB)
     */
    protected function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return 'N/A';
        }

        if ($bytes < self::BYTES_PER_KB) {
            return $bytes . ' B';
        }

        if ($bytes < self::BYTES_PER_MB) {
            return round($bytes / self::BYTES_PER_KB, 2) . ' KB';
        }

        if ($bytes < self::BYTES_PER_GB) {
            return round($bytes / self::BYTES_PER_MB, 2) . ' MB';
        }

        return round($bytes / self::BYTES_PER_GB, 2) . ' GB';
    }

    /**
     * Format milliseconds to human-readable format (ms, seconds)
     */
    protected function formatMilliseconds(?int $ms): string
    {
        if ($ms === null) {
            return 'N/A';
        }

        if ($ms < self::MS_PER_SECOND) {
            return $ms . 'ms';
        }

        return round($ms / self::MS_PER_SECOND, 2) . 's';
    }

    /**
     * Get formatted memory start
     */
    public function getFormattedMemoryStartAttribute(): string
    {
        return $this->formatBytes($this->memory_start_bytes);
    }

    /**
     * Get formatted memory end
     */
    public function getFormattedMemoryEndAttribute(): string
    {
        return $this->formatBytes($this->memory_end_bytes);
    }

    /**
     * Get formatted memory peak start
     */
    public function getFormattedMemoryPeakStartAttribute(): string
    {
        return $this->formatBytes($this->memory_peak_start_bytes);
    }

    /**
     * Get formatted memory peak end
     */
    public function getFormattedMemoryPeakEndAttribute(): string
    {
        return $this->formatBytes($this->memory_peak_end_bytes);
    }

    /**
     * Get formatted memory peak delta (with +/- sign)
     */
    public function getFormattedMemoryPeakDeltaAttribute(): string
    {
        if ($this->memory_peak_delta_bytes === null) {
            return 'N/A';
        }

        $formatted = $this->formatBytes(abs($this->memory_peak_delta_bytes));
        $sign = $this->memory_peak_delta_bytes >= 0 ? '+' : '-';
        return $sign . $formatted;
    }

    /**
     * Get formatted CPU user time
     */
    public function getFormattedCpuUserAttribute(): string
    {
        return $this->formatMilliseconds($this->cpu_user_ms);
    }

    /**
     * Get formatted CPU system time
     */
    public function getFormattedCpuSysAttribute(): string
    {
        return $this->formatMilliseconds($this->cpu_sys_ms);
    }

    /**
     * Get total CPU time (user + sys)
     */
    public function getCpuTotalMsAttribute(): ?int
    {
        if ($this->cpu_user_ms === null && $this->cpu_sys_ms === null) {
            return null;
        }

        return ($this->cpu_user_ms ?? 0) + ($this->cpu_sys_ms ?? 0);
    }

    /**
     * Get formatted total CPU time
     */
    public function getFormattedCpuTotalAttribute(): string
    {
        return $this->formatMilliseconds($this->cpu_total_ms);
    }

    /**
     * Calculate memory delta (end - start)
     */
    public function getMemoryDeltaBytesAttribute(): ?int
    {
        if ($this->memory_start_bytes === null || $this->memory_end_bytes === null) {
            return null;
        }

        return $this->memory_end_bytes - $this->memory_start_bytes;
    }

    /**
     * Get formatted memory delta (with +/- sign)
     */
    public function getFormattedMemoryDeltaAttribute(): string
    {
        $delta = $this->memory_delta_bytes;

        if ($delta === null) {
            return 'N/A';
        }

        $formatted = $this->formatBytes(abs($delta));
        $sign = $delta >= 0 ? '+' : '-';
        return $sign . $formatted;
    }
}

