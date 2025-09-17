<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
    ];

    /**
     * Get the teacher who owns this class.
     *
     * @return BelongsTo
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get all members (students and assistants) of this class.
     *
     * @return BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_members')
                    ->withPivot('role_in_class')
                    ->withTimestamps();
    }

    /**
     * Get only students in this class.
     *
     * @return BelongsToMany
     */
    public function students(): BelongsToMany
    {
        return $this->members()->wherePivot('role_in_class', 'student');
    }

    /**
     * Get only assistants in this class.
     *
     * @return BelongsToMany
     */
    public function assistants(): BelongsToMany
    {
        return $this->members()->wherePivot('role_in_class', 'assistant');
    }

    /**
     * Get assignments for this class.
     *
     * @return HasMany
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Add a member to this class.
     *
     * @param User $user
     * @param string $role
     * @return void
     */
    public function addMember(User $user, string $role = 'student'): void
    {
        $this->members()->syncWithoutDetaching([
            $user->id => ['role_in_class' => $role]
        ]);
    }

    /**
     * Remove a member from this class.
     *
     * @param User $user
     * @return void
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Check if user is a member of this class.
     *
     * @param User $user
     * @return bool
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Get member count for this class.
     *
     * @return int
     */
    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    /**
     * Get student count for this class.
     *
     * @return int
     */
    public function getStudentCount(): int
    {
        return $this->students()->count();
    }

    /**
     * Scope to filter classes by level.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter classes by teacher.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $teacherId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}