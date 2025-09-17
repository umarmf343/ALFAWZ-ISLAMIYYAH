<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class MemorizationPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'surahs',
        'daily_target',
        'start_date',
        'end_date',
        'status',
        'is_teacher_visible'
    ];

    protected $casts = [
        'surahs' => 'array',
        'daily_target' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_teacher_visible' => 'boolean'
    ];

    /**
     * Get the user who owns this memorization plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the SRS queue items for this plan.
     */
    public function srsQueue(): HasMany
    {
        return $this->hasMany(SrsQueue::class, 'plan_id');
    }

    /**
     * Get the class associated with this user (for teacher visibility).
     */
    public function class()
    {
        return $this->user->classes()->first();
    }

    /**
     * Check if the plan is active and within date range.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now()->toDateString();
        $startDate = $this->start_date->toDateString();
        $endDate = $this->end_date ? $this->end_date->toDateString() : null;

        return $now >= $startDate && ($endDate === null || $now <= $endDate);
    }

    /**
     * Check if the plan is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get the total number of ayahs in this plan.
     */
    public function getTotalAyahsAttribute(): int
    {
        // This would need to be calculated based on the surahs
        // For now, return a placeholder
        return $this->srsQueue()->count();
    }

    /**
     * Get the completion percentage of this plan.
     */
    public function getCompletionPercentageAttribute(): float
    {
        $total = $this->srsQueue()->count();
        if ($total === 0) return 0;
        
        $completed = $this->srsQueue()->where('confidence_score', '>=', 0.8)->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get the average confidence score for this plan.
     */
    public function getAverageConfidenceAttribute(): float
    {
        return $this->srsQueue()->avg('confidence_score') ?? 0;
    }

    /**
     * Get the number of ayahs due for review today.
     */
    public function getDueTodayCountAttribute(): int
    {
        return $this->srsQueue()
            ->where('due_at', '<=', now())
            ->count();
    }

    /**
     * Mark the plan as completed if all ayahs have high confidence.
     */
    public function checkCompletion(): void
    {
        $totalItems = $this->srsQueue()->count();
        $masteredItems = $this->srsQueue()->where('confidence_score', '>=', 0.9)->count();

        if ($totalItems > 0 && $masteredItems === $totalItems) {
            $this->update(['status' => 'completed']);
        }
    }

    /**
     * Get the estimated completion date based on current progress.
     */
    public function getEstimatedCompletionAttribute(): ?Carbon
    {
        if ($this->status === 'completed') {
            return null;
        }

        $remainingItems = $this->srsQueue()->where('confidence_score', '<', 0.9)->count();
        if ($remainingItems === 0) {
            return now();
        }

        $averageReviewsPerDay = $this->daily_target;
        $daysRemaining = ceil($remainingItems / $averageReviewsPerDay);

        return now()->addDays($daysRemaining);
    }

    /**
     * Scope to filter active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter plans visible to teachers.
     */
    public function scopeTeacherVisible($query)
    {
        return $query->where('is_teacher_visible', true);
    }
}