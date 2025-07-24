<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Issue extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'external_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the issue.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that owns the issue.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the timers for the issue.
     */
    public function timers(): HasMany
    {
        return $this->hasMany(Timer::class);
    }

    /**
     * Get the time entries for the issue.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Scope a query to only include active issues.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only include issues for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include issues for a specific project.
     */
    public function scopeForProject(Builder $query, int $projectId): void
    {
        $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to only include issues with a specific status.
     */
    public function scopeWithStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope a query to only include issues with a specific priority.
     */
    public function scopeWithPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * Get the total time tracked for this issue in seconds.
     */
    public function getTotalTimeAttribute(): int
    {
        return $this->timeEntries()->sum('duration_seconds');
    }

    /**
     * Get formatted total time for this issue.
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
     * Get the priority color for UI display.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => '#10B981',      // Green
            'medium' => '#F59E0B',   // Amber
            'high' => '#EF4444',     // Red
            'urgent' => '#DC2626',   // Dark Red
            default => '#6B7280',    // Gray
        };
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => '#6B7280',        // Gray
            'in_progress' => '#3B82F6', // Blue
            'resolved' => '#10B981',    // Green
            'closed' => '#374151',      // Dark Gray
            default => '#6B7280',       // Gray
        };
    }
}
