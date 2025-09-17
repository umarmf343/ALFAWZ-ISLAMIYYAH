<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'class_id',
        'teacher_id',
        'surah_id',
        'ayah_start',
        'ayah_end',
        'arabic_text',
        'translation',
        'type',
        'difficulty',
        'expected_hasanat',
        'requires_audio',
        'has_hotspots',
        'assigned_at',
        'due_date',
        'status',
        'background_image',
        'audio_url',
        'settings'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'requires_audio' => 'boolean',
        'has_hotspots' => 'boolean',
        'settings' => 'array'
    ];

    /**
     * Get the class this assignment belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the teacher who created this assignment.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get all hotspots for this assignment.
     */
    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class)->orderBy('order_index');
    }

    /**
     * Get all submissions for this assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get all notifications for this assignment.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AssignmentNotification::class);
    }

    /**
     * Get feedback through submissions.
     */
    public function feedback(): HasManyThrough
    {
        return $this->hasManyThrough(Feedback::class, Submission::class);
    }

    /**
     * Check if assignment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }

    /**
     * Check if assignment is due soon (within 24 hours).
     */
    public function isDueSoon(): bool
    {
        return $this->due_date && 
               $this->due_date->isFuture() && 
               $this->due_date->diffInHours(now()) <= 24;
    }

    /**
     * Get completion rate for this assignment across all students.
     */
    public function getCompletionRate(): float
    {
        $totalStudents = $this->class->members()->where('role', 'student')->count();
        if ($totalStudents === 0) return 0;
        
        $completedSubmissions = $this->submissions()
            ->whereIn('status', ['submitted', 'reviewed', 'completed'])
            ->count();
            
        return ($completedSubmissions / $totalStudents) * 100;
    }

    /**
     * Publish the assignment and notify students.
     */
    public function publish(): bool
    {
        $this->update([
            'status' => 'published',
            'assigned_at' => now()
        ]);
        
        // Create notifications for all class members
        $this->createNotificationsForStudents('assignment_created');
        
        return true;
    }

    /**
     * Create notifications for all students in the class.
     */
    public function createNotificationsForStudents(string $type): void
    {
        $students = $this->class->members()
            ->where('role', 'student')
            ->where('is_active', true)
            ->get();
            
        foreach ($students as $student) {
            AssignmentNotification::create([
                'assignment_id' => $this->id,
                'student_id' => $student->user_id,
                'type' => $type,
                'title' => $this->getNotificationTitle($type),
                'message' => $this->getNotificationMessage($type),
                'data' => [
                    'assignment_title' => $this->title,
                    'class_name' => $this->class->name,
                    'due_date' => $this->due_date?->toISOString()
                ]
            ]);
        }
    }

    /**
     * Get notification title based on type.
     */
    private function getNotificationTitle(string $type): string
    {
        return match($type) {
            'assignment_created' => 'New Assignment Available',
            'assignment_due_soon' => 'Assignment Due Soon',
            'assignment_overdue' => 'Assignment Overdue',
            default => 'Assignment Update'
        };
    }

    /**
     * Get notification message based on type.
     */
    private function getNotificationMessage(string $type): string
    {
        return match($type) {
            'assignment_created' => "New assignment '{$this->title}' has been assigned to your class.",
            'assignment_due_soon' => "Assignment '{$this->title}' is due soon. Please complete it before the deadline.",
            'assignment_overdue' => "Assignment '{$this->title}' is now overdue. Please submit as soon as possible.",
            default => "Update for assignment '{$this->title}'."
        };
    }

    /**
     * Scope for published assignments.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for assignments due soon.
     */
    public function scopeDueSoon($query)
    {
        return $query->where('due_date', '>', now())
                    ->where('due_date', '<=', now()->addDay());
    }

    /**
     * Scope for overdue assignments.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
    }
}