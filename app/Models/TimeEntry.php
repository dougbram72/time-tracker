<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'trackable_type',
        'trackable_id',
        'project_id',
        'issue_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'description'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer'
    ];

    /**
     * Get the user that owns the time entry
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
     * Get the project associated with the time entry (convenience relationship)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the issue associated with the time entry (convenience relationship)
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get duration in hours as decimal
     */
    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_seconds / 3600, 2);
    }

    /**
     * Scope to get entries for a specific date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get entries for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get entries for a specific trackable type
     */
    public function scopeForTrackableType($query, $type)
    {
        return $query->where('trackable_type', $type);
    }

    /**
     * Scope to get entries for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }

    /**
     * Scope to get entries for this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('started_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }
}
