<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasanat model for tracking spiritual rewards and gamification
 * 
 * @property int $id
 * @property int $user_id
 * @property string $activity_type
 * @property int $points
 * @property string $description
 * @property array $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Hasanat extends Model
{
    use HasFactory;

    protected $table = 'hasanat';

    protected $fillable = [
        'user_id',
        'activity_type',
        'points',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'points' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who earned this hasanat
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get hasanat records for a specific user
     * 
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForUser(int $userId)
    {
        return static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate total hasanat for a user
     * 
     * @param int $userId
     * @return int
     */
    public static function getTotalForUser(int $userId): int
    {
        return static::where('user_id', $userId)->sum('points');
    }

    /**
     * Get hasanat by activity type for a user
     * 
     * @param int $userId
     * @param string $activityType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByActivityType(int $userId, string $activityType)
    {
        return static::where('user_id', $userId)
            ->where('activity_type', $activityType)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Award hasanat to a user
     * 
     * @param int $userId
     * @param string $activityType
     * @param int $points
     * @param string $description
     * @param array $metadata
     * @return static
     */
    public static function award(
        int $userId,
        string $activityType,
        int $points,
        string $description,
        array $metadata = []
    ): static {
        return static::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'points' => $points,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get leaderboard data
     * 
     * @param int $limit
     * @param string|null $period (weekly, monthly, all-time)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLeaderboard(int $limit = 10, ?string $period = null)
    {
        $query = static::selectRaw('user_id, SUM(points) as total_points')
            ->with('user:id,name,email')
            ->groupBy('user_id');

        if ($period === 'weekly') {
            $query->where('created_at', '>=', now()->subWeek());
        } elseif ($period === 'monthly') {
            $query->where('created_at', '>=', now()->subMonth());
        }

        return $query->orderBy('total_points', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Activity type constants
     */
    public const ACTIVITY_ASSIGNMENT_COMPLETION = 'assignment_completion';
    public const ACTIVITY_PERFECT_RECITATION = 'perfect_recitation';
    public const ACTIVITY_DAILY_PRACTICE = 'daily_practice';
    public const ACTIVITY_HELPING_PEER = 'helping_peer';
    public const ACTIVITY_STREAK_BONUS = 'streak_bonus';
    public const ACTIVITY_ACHIEVEMENT_UNLOCK = 'achievement_unlock';
    public const ACTIVITY_TAJWEED_MASTERY = 'tajweed_mastery';
    public const ACTIVITY_MEMORIZATION = 'memorization';
}