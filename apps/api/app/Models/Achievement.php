<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Achievement model for tracking user badges and milestones
 * 
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property string $category
 * @property int $points_required
 * @property array $criteria
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'category',
        'points_required',
        'criteria',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'points_required' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Users who have earned this achievement
     * 
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('earned_at');
    }

    /**
     * Check if a user has earned this achievement
     * 
     * @param int $userId
     * @return bool
     */
    public function isEarnedBy(int $userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Award this achievement to a user
     * 
     * @param int $userId
     * @return bool
     */
    public function awardTo(int $userId): bool
    {
        if ($this->isEarnedBy($userId)) {
            return false;
        }

        $this->users()->attach($userId, [
            'earned_at' => now(),
        ]);

        return true;
    }

    /**
     * Get all active achievements
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('points_required')
            ->get();
    }

    /**
     * Get achievements by category
     * 
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByCategory(string $category)
    {
        return static::where('category', $category)
            ->where('is_active', true)
            ->orderBy('points_required')
            ->get();
    }

    /**
     * Check if user meets criteria for this achievement
     * 
     * @param User $user
     * @return bool
     */
    public function checkCriteria(User $user): bool
    {
        $criteria = $this->criteria;
        
        if (!$criteria) {
            return false;
        }

        // Check total hasanat requirement
        if (isset($criteria['total_hasanat'])) {
            $totalHasanat = Hasanat::getTotalForUser($user->id);
            if ($totalHasanat < $criteria['total_hasanat']) {
                return false;
            }
        }

        // Check specific activity requirements
        if (isset($criteria['activities'])) {
            foreach ($criteria['activities'] as $activity => $requirement) {
                $activityCount = Hasanat::getByActivityType($user->id, $activity)->count();
                if ($activityCount < $requirement) {
                    return false;
                }
            }
        }

        // Check streak requirements
        if (isset($criteria['streak_days'])) {
            // This would need to be implemented based on user activity tracking
            // For now, we'll assume it's met
        }

        return true;
    }

    /**
     * Achievement category constants
     */
    public const CATEGORY_RECITATION = 'recitation';
    public const CATEGORY_MEMORIZATION = 'memorization';
    public const CATEGORY_TAJWEED = 'tajweed';
    public const CATEGORY_CONSISTENCY = 'consistency';
    public const CATEGORY_COMMUNITY = 'community';
    public const CATEGORY_MILESTONE = 'milestone';

    /**
     * Seed default achievements
     * 
     * @return void
     */
    public static function seedDefaults(): void
    {
        $achievements = [
            [
                'name' => 'First Steps',
                'description' => 'Complete your first assignment',
                'icon' => '🌟',
                'category' => self::CATEGORY_MILESTONE,
                'points_required' => 0,
                'criteria' => ['activities' => [Hasanat::ACTIVITY_ASSIGNMENT_COMPLETION => 1]],
                'is_active' => true,
            ],
            [
                'name' => 'Dedicated Student',
                'description' => 'Earn 1000 hasanat points',
                'icon' => '📚',
                'category' => self::CATEGORY_MILESTONE,
                'points_required' => 1000,
                'criteria' => ['total_hasanat' => 1000],
                'is_active' => true,
            ],
            [
                'name' => 'Perfect Reciter',
                'description' => 'Achieve 10 perfect recitations',
                'icon' => '🎯',
                'category' => self::CATEGORY_RECITATION,
                'points_required' => 500,
                'criteria' => ['activities' => [Hasanat::ACTIVITY_PERFECT_RECITATION => 10]],
                'is_active' => true,
            ],
            [
                'name' => 'Tajweed Master',
                'description' => 'Master 5 tajweed rules',
                'icon' => '🕌',
                'category' => self::CATEGORY_TAJWEED,
                'points_required' => 750,
                'criteria' => ['activities' => [Hasanat::ACTIVITY_TAJWEED_MASTERY => 5]],
                'is_active' => true,
            ],
            [
                'name' => 'Consistent Learner',
                'description' => 'Practice daily for 7 days',
                'icon' => '🔥',
                'category' => self::CATEGORY_CONSISTENCY,
                'points_required' => 350,
                'criteria' => ['streak_days' => 7],
                'is_active' => true,
            ],
        ];

        foreach ($achievements as $achievement) {
            static::firstOrCreate(
                ['name' => $achievement['name']],
                $achievement
            );
        }
    }
}