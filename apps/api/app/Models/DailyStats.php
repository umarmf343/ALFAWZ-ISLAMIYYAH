<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily statistics model for tracking user progress.
 * Stores daily recitation stats, hasanat earned, and streak information.
 */
class DailyStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'verses_read',
        'hasanat_earned',
        'time_spent',
        'streak_days',
        'daily_goal',
        'goal_achieved',
    ];

    protected $casts = [
        'date' => 'date',
        'verses_read' => 'integer',
        'hasanat_earned' => 'integer',
        'time_spent' => 'integer',
        'streak_days' => 'integer',
        'daily_goal' => 'integer',
        'goal_achieved' => 'boolean',
    ];

    /**
     * Get the user that owns the daily stats.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if daily goal is achieved.
     */
    public function checkGoalAchievement(): bool
    {
        $achieved = $this->verses_read >= $this->daily_goal;
        if ($achieved && !$this->goal_achieved) {
            $this->update(['goal_achieved' => true]);
        }
        return $achieved;
    }

    /**
     * Get progress percentage for the day.
     */
    public function getProgressPercentage(): float
    {
        if ($this->daily_goal === 0) {
            return 0;
        }
        return min(100, ($this->verses_read / $this->daily_goal) * 100);
    }
}