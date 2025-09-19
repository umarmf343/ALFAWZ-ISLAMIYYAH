<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Hasanat;
use App\Models\Achievement;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, MustVerifyEmailTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'gender',
        'country',
        'city',
        'timezone',
        'preferred_language',
        'profile_picture_url',
        'bio',
        'tajweed_level',
        'memorization_goal',
        'daily_goal_minutes',
        'streak_count',
        'total_hasanat',
        'last_activity_at',
        'is_active',
        'email_verified_at',
        'hasanat_total',
        'role',
        'status',
        'suspended_at',
        // Notification preferences
        'email_notifications',
        'daily_summary',
        'review_completed_notifications',
        'student_progress_notifications',
        'system_notifications',
        'leaderboard_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'suspended_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'email_notifications' => 'boolean',
        'daily_summary' => 'boolean',
        'review_completed_notifications' => 'boolean',
        'student_progress_notifications' => 'boolean',
        'system_notifications' => 'boolean',
        'leaderboard_preferences' => 'array',
    ];

    /**
     * Get all hasanat records for this user
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasanat()
    {
        return $this->hasMany(Hasanat::class);
    }

    /**
     * Get achievements earned by this user
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('earned_at');
    }

    /**
     * Calculate total hasanat points for this user
     * 
     * @return int
     */
    public function getTotalHasanatAttribute(): int
    {
        return $this->hasanat()->sum('points');
    }

    /**
     * Get user's current level based on hasanat
     * 
     * @return int
     */
    public function getCurrentLevelAttribute(): int
    {
        $totalHasanat = $this->getTotalHasanatAttribute();
        
        // Level calculation: every 1000 hasanat = 1 level
        return max(1, floor($totalHasanat / 1000) + 1);
    }

    /**
     * Get hasanat needed for next level
     * 
     * @return int
     */
    public function getHasanatToNextLevelAttribute(): int
    {
        $currentLevel = $this->getCurrentLevelAttribute();
        $nextLevelRequirement = $currentLevel * 1000;
        $totalHasanat = $this->getTotalHasanatAttribute();
        
        return max(0, $nextLevelRequirement - $totalHasanat);
    }

    /**
     * Check if user has earned a specific achievement
     * 
     * @param int $achievementId
     * @return bool
     */
    public function hasAchievement(int $achievementId): bool
    {
        return $this->achievements()->where('achievement_id', $achievementId)->exists();
    }

    /**
     * Award hasanat to this user
     * 
     * @param string $activityType
     * @param int $points
     * @param string $description
     * @param array $metadata
     * @return Hasanat
     */
    public function awardHasanat(
        string $activityType,
        int $points,
        string $description,
        array $metadata = []
    ): Hasanat {
        return Hasanat::award($this->id, $activityType, $points, $description, $metadata);
    }

    /**
     * Get classes where this user is a teacher.
     *
     * @return HasMany
     */
    public function teachingClasses(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'teacher_id');
    }

    /**
     * Get classes where this user is a member (student or assistant).
     *
     * @return BelongsToMany
     */
    public function memberClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_members', 'user_id', 'class_id')
                    ->withPivot('role_in_class')
                    ->withTimestamps();
    }

    /**
     * Get classes where this user participates as a student.
     */
    public function studentClasses(): BelongsToMany
    {
        return $this->memberClasses()->wherePivot('role_in_class', 'student');
    }

    /**
     * Get assignments created by this user (if teacher).
     *
     * @return HasMany
     */
    public function createdAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    /**
     * Get submissions made by this user (if student).
     *
     * @return HasMany
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    /**
     * Get feedback given by this user (if teacher).
     *
     * @return HasMany
     */
    public function givenFeedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'teacher_id');
    }

    /**
     * Get Quran progress records for this user.
     *
     * @return HasMany
     */
    public function quranProgress(): HasMany
    {
        return $this->hasMany(QuranProgress::class);
    }

    /**
     * Get SRS queue items for this user.
     *
     * @return HasMany
     */
    public function srsQueue(): HasMany
    {
        return $this->hasMany(SrsQueue::class);
    }

    /**
     * Get payments made by this user.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get bookmarks created by this user.
     *
     * @return HasMany
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Get the leaderboard entry for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function leaderboardEntry()
    {
        return $this->hasOne(LeaderboardEntry::class);
    }

    /**
     * Get invites sent by this user.
     *
     * @return HasMany
     */
    public function sentLeaderboardInvites(): HasMany
    {
        return $this->hasMany(LeaderboardInvite::class, 'sender_id');
    }

    /**
     * Get invites received by this user.
     *
     * @return HasMany
     */
    public function receivedLeaderboardInvites(): HasMany
    {
        return $this->hasMany(LeaderboardInvite::class, 'receiver_id');
    }

    /**
     * Check if user is a teacher.
     *
     * @return bool
     */
    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    /**
     * Check if user is a student.
     *
     * @return bool
     */
    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get user's teachers (if student).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTeachers()
    {
        return $this->memberClasses()
                    ->with('teacher')
                    ->get()
                    ->pluck('teacher')
                    ->unique('id');
    }

    /**
     * Get user's students (if teacher).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStudents()
    {
        return $this->teachingClasses()
                    ->with(['members' => function ($query) {
                        $query->wherePivot('role_in_class', 'student');
                    }])
                    ->get()
                    ->pluck('members')
                    ->flatten()
                    ->unique('id');
    }

    /**
     * Send the custom email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}
