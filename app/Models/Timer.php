<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Timer extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'trackable_type',
        'trackable_id',
        'project_id',
        'issue_id',
        'started_at',
        'paused_at',
        'stopped_at',
        'elapsed_seconds',
        'status',
        'description'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime',
        'elapsed_seconds' => 'integer'
    ];

    protected $attributes = [
        'elapsed_seconds' => 0
    ];

    // Timer status constants
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_STOPPED = 'stopped';

    /**
     * Get the user that owns the timer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trackable entity (Project or Issue)
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the project associated with the timer (convenience relationship)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the issue associated with the timer (convenience relationship)
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Start the timer
     */
    public function start(): void
    {
        $this->update([
            'started_at' => now(),
            'status' => self::STATUS_RUNNING,
            'paused_at' => null,
            'stopped_at' => null
        ]);
    }

    /**
     * Pause the timer
     */
    public function pause(): void
    {
        if ($this->status === self::STATUS_RUNNING) {
            $this->updateElapsedTime();
            $this->update([
                'paused_at' => now(),
                'status' => self::STATUS_PAUSED
            ]);
        }
    }

    /**
     * Resume the timer from paused state
     */
    public function resume(): void
    {
        if ($this->status === self::STATUS_PAUSED) {
            $this->update([
                'started_at' => now(),
                'paused_at' => null,
                'status' => self::STATUS_RUNNING
            ]);
        }
    }

    /**
     * Stop the timer and create time entry
     */
    public function stop(): TimeEntry
    {
        $this->updateElapsedTime();
        $this->update([
            'stopped_at' => now(),
            'status' => self::STATUS_STOPPED
        ]);

        // Create time entry
        return TimeEntry::create([
            'user_id' => $this->user_id,
            'trackable_type' => $this->trackable_type,
            'trackable_id' => $this->trackable_id,
            'project_id' => $this->project_id,
            'issue_id' => $this->issue_id,
            'started_at' => $this->started_at,
            'ended_at' => $this->stopped_at,
            'duration_seconds' => $this->elapsed_seconds,
            'description' => $this->description
        ]);
    }

    /**
     * Update elapsed time based on current status
     */
    public function updateElapsedTime(): void
    {
        if ($this->status === self::STATUS_RUNNING && $this->started_at) {
            $additionalSeconds = $this->started_at->diffInSeconds(now());
            $this->elapsed_seconds = ($this->elapsed_seconds ?? 0) + $additionalSeconds;
            $this->save();
        }
    }

    /**
     * Get current elapsed time in seconds
     */
    public function getCurrentElapsedSeconds(): int
    {
        $elapsed = $this->elapsed_seconds ?? 0;
        
        if ($this->status === self::STATUS_RUNNING && $this->started_at) {
            $elapsed += $this->started_at->diffInSeconds(now());
        }
        
        return $elapsed;
    }

    /**
     * Check if timer is active (running or paused)
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_RUNNING, self::STATUS_PAUSED]);
    }

    /**
     * Scope to get active timers for a user
     */
    public function scopeActiveForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->whereIn('status', [self::STATUS_RUNNING, self::STATUS_PAUSED]);
    }

    /**
     * Scope to get running timers for a user
     */
    public function scopeRunningForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->where('status', self::STATUS_RUNNING);
    }
}
