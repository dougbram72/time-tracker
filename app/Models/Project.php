<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{

    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the issues for the project.
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Get the timers for the project.
     */
    public function timers(): HasMany
    {
        return $this->hasMany(Timer::class);
    }

    /**
     * Get the time entries for the project.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only include projects for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Get the total time tracked for this project in seconds.
     */
    public function getTotalTimeAttribute(): int
    {
        return $this->timeEntries()->sum('duration_seconds');
    }

    /**
     * Get formatted total time for this project.
     */
    public function getFormattedTotalTimeAttribute(): string
    {
        $seconds = $this->total_time;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds % 60);
        }
        
        return sprintf('%02d:%02d', $minutes, $seconds % 60);
    }

    /**
     * Get the number of active issues for this project.
     */
    public function getActiveIssuesCountAttribute(): int
    {
        return $this->issues()->where('is_active', true)->count();
    }
}
