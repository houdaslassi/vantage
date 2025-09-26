<?php

namespace houdaslassi\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;

class QueueJobRun extends Model
{
    protected $table = 'queue_job_runs';

    protected static $unguarded = true;

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function retriedFrom()
    {
        return $this->belongsTo(self::class, 'retried_from_id');
    }

    public function retries()
    {
        return $this->hasMany(self::class, 'retried_from_id');
    }

}
