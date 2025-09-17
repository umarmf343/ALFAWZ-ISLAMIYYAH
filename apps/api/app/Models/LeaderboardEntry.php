<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hasanat',
        'surahs_completed',
        'tasks_completed',
        'sujud_count',
        'memorization_score',
        'streak_days',
        'is_public',
        'last_active',
    ];

    protected $casts = [
        'hasanat' => 'integer',
        'surahs_completed' => 'integer',
        'tasks_completed' => 'integer',
        'sujud_count' => 'integer',
        'memorization_score' => 'float',
        'streak_days' => 'integer',
        'is_public' => 'boolean',
        'last_active' => 'datetime',
    ];

    /**
     * Get the user that owns the leaderboard entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rank of this entry based on hasanat.
     */
    public function getRankAttribute(): int
    {
        return self::where('hasanat', '>', $this->hasanat)
            ->where('is_public', true)
            ->count() + 1;
    }

    /**
     * Get the total score for ranking.
     */
    public function getTotalScoreAttribute(): float
    {
        return $this->hasanat + 
               ($this->surahs_completed * 50) + 
               ($this->tasks_completed * 10) + 
               ($this->sujud_count * 5) + 
               ($this->memorization_score * 2) + 
               ($this->streak_days * 20);
    }

    /**
     * Scope for public entries only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for active users (active within last 30 days).
     */
    public function scopeActive($query)
    {
        return $query->where('last_active', '>=', now()->subDays(30));
    }

    /**
     * Get top performers.
     */
    public static function getTopPerformers($limit = 10)
    {
        return self::public()
            ->with(['user' => fn($q) => $q->select('id', 'name')])
            ->orderByDesc('hasanat')
            ->take($limit)
            ->get();
    }
}