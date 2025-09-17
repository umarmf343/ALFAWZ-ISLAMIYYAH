<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranProgress extends Model
{
    use HasFactory;

    protected $table = 'quran_progress';

    protected $fillable = [
        'user_id',
        'surah_id',
        'ayah_id',
        'recited_count',
        'memorized_confidence',
        'hasanat',
        'last_seen_at'
    ];

    protected $casts = [
        'surah_id' => 'integer',
        'ayah_id' => 'integer',
        'recited_count' => 'integer',
        'memorized_confidence' => 'float',
        'hasanat' => 'integer',
        'last_seen_at' => 'datetime'
    ];

    /**
     * Get the user who owns this progress record.
     *
     * @return BelongsTo User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this ayah has been memorized (confidence >= 0.8).
     *
     * @return bool True if memorized
     */
    public function isMemorized(): bool
    {
        return $this->memorized_confidence >= 0.8;
    }

    /**
     * Check if this ayah needs review (not seen recently).
     *
     * @param int $daysSinceLastSeen Days threshold for review
     * @return bool True if needs review
     */
    public function needsReview(int $daysSinceLastSeen = 7): bool
    {
        if (!$this->last_seen_at) {
            return true;
        }
        
        return $this->last_seen_at->diffInDays(now()) >= $daysSinceLastSeen;
    }

    /**
     * Get confidence level as a percentage.
     *
     * @return int Confidence percentage (0-100)
     */
    public function getConfidencePercentage(): int
    {
        return (int) round($this->memorized_confidence * 100);
    }

    /**
     * Get confidence level description.
     *
     * @return string Confidence description
     */
    public function getConfidenceLevel(): string
    {
        $confidence = $this->memorized_confidence;
        
        if ($confidence >= 0.9) {
            return 'Excellent';
        } elseif ($confidence >= 0.8) {
            return 'Good';
        } elseif ($confidence >= 0.6) {
            return 'Fair';
        } elseif ($confidence >= 0.4) {
            return 'Needs Practice';
        } else {
            return 'Beginner';
        }
    }

    /**
     * Increment recitation count and update last seen timestamp.
     *
     * @param int $hasanatEarned Additional hasanat earned
     * @return void
     */
    public function recordRecitation(int $hasanatEarned = 0): void
    {
        $this->increment('recited_count');
        $this->increment('hasanat', $hasanatEarned);
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Update memorization confidence based on performance.
     *
     * @param float $performanceScore Score from 0.0 to 1.0
     * @return void
     */
    public function updateConfidence(float $performanceScore): void
    {
        // Simple confidence adjustment algorithm
        $currentConfidence = $this->memorized_confidence;
        $adjustment = ($performanceScore - $currentConfidence) * 0.1;
        
        $newConfidence = max(0.0, min(1.0, $currentConfidence + $adjustment));
        $this->update(['memorized_confidence' => $newConfidence]);
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
     * Scope to filter by surah.
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
     * Scope to filter memorized ayahs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minConfidence Minimum confidence threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMemorized($query, float $minConfidence = 0.8)
    {
        return $query->where('memorized_confidence', '>=', $minConfidence);
    }

    /**
     * Scope to filter ayahs needing review.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $daysSince Days since last seen
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsReview($query, int $daysSince = 7)
    {
        return $query->where(function ($q) use ($daysSince) {
            $q->whereNull('last_seen_at')
              ->orWhere('last_seen_at', '<=', now()->subDays($daysSince));
        });
    }
}