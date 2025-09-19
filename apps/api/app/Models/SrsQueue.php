<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SrsQueue extends Model
{
    use HasFactory;

    protected $table = 'srs_queues';

    protected $fillable = [
        'user_id',
        'plan_id',
        'surah_id',
        'ayah_id',
        'due_at',
        'ease_factor',
        'interval',
        'repetitions',
        'confidence_score',
        'review_count'
    ];

    protected $casts = [
        'surah_id' => 'integer',
        'ayah_id' => 'integer',
        'due_at' => 'datetime',
        'ease_factor' => 'float',
        'interval' => 'integer',
        'repetitions' => 'integer',
        'confidence_score' => 'float',
        'review_count' => 'integer'
    ];

    /**
     * Get the user who owns this SRS queue item.
     *
     * @return BelongsTo User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the memorization plan this item belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MemorizationPlan::class, 'plan_id');
    }

    /**
     * Get the progress record for this ayah.
     */
    public function progress()
    {
        return $this->hasOne(QuranProgress::class, function ($query) {
            $query->where('user_id', $this->user_id)
                  ->where('surah_id', $this->surah_id)
                  ->where('ayah_number', $this->ayah_id);
        });
    }

    /**
     * Check if this item is due for review.
     */
    public function isDue(): bool
    {
        return $this->due_at <= now();
    }

    /**
     * Check if this item is overdue for review.
     *
     * @param int $gracePeriodHours Grace period in hours
     * @return bool True if overdue
     */
    public function isOverdue(int $gracePeriodHours = 24): bool
    {
        return $this->due_at->addHours($gracePeriodHours) < now();
    }

    /**
     * Get days until next review.
     *
     * @return int Days until review (negative if overdue)
     */
    public function getDaysUntilReview(): int
    {
        return (int) now()->diffInDays($this->due_at, false);
    }

    /**
     * Check if this ayah is considered mastered.
     */
    public function isMastered(): bool
    {
        return $this->confidence_score >= 0.9 && $this->repetitions >= 3;
    }

    /**
     * Get the confidence percentage.
     */
    public function getConfidencePercentage(): int
    {
        return (int) round($this->confidence_score * 100);
    }

    /**
     * Update SRS parameters based on SM-2 algorithm.
     *
     * @param int $quality Quality score (0-5)
     * @return void
     */
    public function applyReview(float $confidenceScore): void
    {
        $quality = max(0, min(5, (int) round($confidenceScore * 5)));

        $this->review_count++;

        if ($quality < 3) {
            $this->repetitions = 0;
            $this->interval = 1;
        } else {
            $this->repetitions++;

            $this->ease_factor = max(
                1.3,
                $this->ease_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02))
            );

            if ($this->repetitions === 1) {
                $this->interval = 1;
            } elseif ($this->repetitions === 2) {
                $this->interval = 6;
            } else {
                $this->interval = max(1, (int) round($this->interval * $this->ease_factor));
            }
        }

        $this->confidence_score = min(1.0, max(0.0, $confidenceScore));
        $this->due_at = now()->addDays(max(1, $this->interval));

        $this->save();
    }

    /**
     * Get difficulty level based on ease factor.
     *
     * @return string Difficulty description
     */
    public function getDifficultyLevel(): string
    {
        $ease = $this->ease_factor;
        
        if ($ease >= 2.5) {
            return 'Easy';
        } elseif ($ease >= 2.0) {
            return 'Medium';
        } elseif ($ease >= 1.7) {
            return 'Hard';
        } else {
            return 'Very Hard';
        }
    }

    /**
     * Get the unique identifier for this ayah.
     *
     * @return string Surah:Ayah format
     */
    public function getAyahIdentifier(): string
    {
        return "{$this->surah_id}:{$this->ayah_id}";
    }

    /**
     * Get the next review date in a human-readable format.
     */
    public function getNextReviewAttribute(): string
    {
        return $this->due_at->diffForHumans();
    }

    /**
     * Get the difficulty level based on confidence score.
     */
    public function getDifficultyLevelAttribute(): string
    {
        if ($this->confidence_score >= 0.8) {
            return 'easy';
        } elseif ($this->confidence_score >= 0.5) {
            return 'medium';
        } else {
            return 'hard';
        }
    }

    /**
     * Get review priority score (higher = more urgent).
     *
     * @return float Priority score
     */
    public function getPriorityScore(): float
    {
        $daysOverdue = max(0, -$this->getDaysUntilReview());
        $difficultyMultiplier = 3.0 - $this->ease_factor; // Lower ease = higher priority
        
        return $daysOverdue + $difficultyMultiplier;
    }

    /**
     * Reset SRS parameters to initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->update([
            'interval' => 1,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'confidence_score' => 0,
            'review_count' => 0,
            'due_at' => now()
        ]);
    }

    /**
     * Scope to filter items due for review.
     */
    public function scopeDue($query)
    {
        return $query->where('due_at', '<=', now());
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter mastered items.
     */
    public function scopeMastered($query)
    {
        return $query->where('confidence_score', '>=', 0.9)
                    ->where('repetitions', '>=', 3);
    }

    /**
     * Scope to order by due date.
     */
    public function scopeOrderByDue($query)
    {
        return $query->orderBy('due_at');
    }

    /**
     * Scope to filter items by surah.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $surahId Surah ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySurah($query, int $surahId)
    {
        return $query->where('surah_id', $surahId);
    }

    /**
     * Scope to order by review priority.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw('(CASE WHEN next_review_at <= NOW() THEN DATEDIFF(NOW(), next_review_at) ELSE 0 END) + (3.0 - ease_factor) DESC');
    }

    /**
     * Scope to filter by difficulty level.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $level Difficulty level (easy, medium, hard, very_hard)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDifficulty($query, string $level)
    {
        switch (strtolower($level)) {
            case 'easy':
                return $query->where('confidence_score', '>=', 0.8);
            case 'medium':
                return $query->whereBetween('confidence_score', [0.5, 0.79]);
            case 'hard':
                return $query->where('confidence_score', '<', 0.5);
            default:
                return $query;
        }
    }

    /**
     * Scope to get items for a specific surah.
     */
    public function scopeForSurah($query, int $surahId)
    {
        return $query->where('surah_id', $surahId);
    }
}