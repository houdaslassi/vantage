<?php

namespace houdaslassi\Vantage\Models;

use Illuminate\Database\Eloquent\Model;

class QueueJobRun extends Model
{
    protected $table = 'queue_job_runs';

    protected static $unguarded = true;

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'job_tags'    => 'array',
        'payload'     => 'array',
    ];

    /**
     * Get the job that this was retried from
     */
    public function retriedFrom()
    {
        return $this->belongsTo(self::class, 'retried_from_id');
    }

    /**
     * Get all retry attempts of this job
     */
    public function retries()
    {
        return $this->hasMany(self::class, 'retried_from_id');
    }

    /**
     * Get payload as decoded array
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
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('job_tags', strtolower($tag));
    }

    /**
     * Scope: Filter by any of multiple tags
     */
    public function scopeWithAnyTag($query, array $tags)
    {
        return $query->where(function($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('job_tags', strtolower($tag));
            }
        });
    }

    /**
     * Scope: Filter by all tags (must have all)
     */
    public function scopeWithAllTags($query, array $tags)
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('job_tags', strtolower($tag));
        }
        return $query;
    }

    /**
     * Scope: Exclude jobs with specific tag
     */
    public function scopeWithoutTag($query, string $tag)
    {
        return $query->where(function($q) use ($tag) {
            $q->whereNull('job_tags')
              ->orWhereJsonDoesntContain('job_tags', strtolower($tag));
        });
    }

    /**
     * Scope: Filter by job class
     */
    public function scopeOfClass($query, string $class)
    {
        return $query->where('job_class', $class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Failed jobs only
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Successful jobs only
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope: Processing jobs only
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Check if job has specific tag
     */
    public function hasTag(string $tag): bool
    {
        if (!$this->job_tags) {
            return false;
        }

        return in_array(strtolower($tag), array_map('strtolower', $this->job_tags));
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_ms) {
            return 'N/A';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        return round($this->duration_ms / 1000, 2) . 's';
    }
}

