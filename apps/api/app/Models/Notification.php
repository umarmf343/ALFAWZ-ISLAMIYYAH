<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Notification types constants.
     */
    const TYPE_ASSIGNMENT_CREATED = 'assignment_created';
    const TYPE_ASSIGNMENT_PUBLISHED = 'assignment_published';
    const TYPE_ASSIGNMENT_DUE_SOON = 'assignment_due_soon';
    const TYPE_ASSIGNMENT_OVERDUE = 'assignment_overdue';
    const TYPE_SUBMISSION_REVIEWED = 'submission_reviewed';
    const TYPE_FEEDBACK_RECEIVED = 'feedback_received';
    const TYPE_HASANAT_AWARDED = 'hasanat_awarded';
    const TYPE_ACHIEVEMENT_UNLOCKED = 'achievement_unlocked';

    /**
     * Available notification types.
     *
     * @var array
     */
    public static array $types = [
        self::TYPE_ASSIGNMENT_CREATED => 'New Assignment Created',
        self::TYPE_ASSIGNMENT_PUBLISHED => 'Assignment Published',
        self::TYPE_ASSIGNMENT_DUE_SOON => 'Assignment Due Soon',
        self::TYPE_ASSIGNMENT_OVERDUE => 'Assignment Overdue',
        self::TYPE_SUBMISSION_REVIEWED => 'Submission Reviewed',
        self::TYPE_FEEDBACK_RECEIVED => 'Feedback Received',
        self::TYPE_HASANAT_AWARDED => 'Hasanat Awarded',
        self::TYPE_ACHIEVEMENT_UNLOCKED => 'Achievement Unlocked'
    ];
    
    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($notification) {
            if (empty($notification->id)) {
                $notification->id = (string) Str::uuid();
            }
        });
    }
    
    /**
     * Get the notifiable entity that the notification belongs to.
     *
     * @return MorphTo
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Mark the notification as read.
     *
     * @return bool True if successfully marked as read
     */
    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return true; // Already read
        }
        
        return $this->forceFill([
            'read_at' => $this->freshTimestamp()
        ])->save();
    }
    
    /**
     * Mark the notification as unread.
     *
     * @return bool True if successfully marked as unread
     */
    public function markAsUnread(): bool
    {
        if (!$this->read_at) {
            return true; // Already unread
        }
        
        return $this->forceFill([
            'read_at' => null
        ])->save();
    }
    
    /**
     * Determine if the notification has been read.
     *
     * @return bool True if notification is read
     */
    public function read(): bool
    {
        return $this->read_at !== null;
    }
    
    /**
     * Determine if the notification is unread.
     *
     * @return bool True if notification is unread
     */
    public function unread(): bool
    {
        return $this->read_at === null;
    }
    
    /**
     * Get the notification title based on type.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return self::$types[$this->type] ?? 'Notification';
    }
    
    /**
     * Get the notification message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->data['message'] ?? '';
    }
    
    /**
     * Get the notification action URL.
     *
     * @return string|null
     */
    public function getActionUrl(): ?string
    {
        return $this->data['action_url'] ?? null;
    }
    
    /**
     * Get the notification icon based on type.
     *
     * @return string
     */
    public function getIcon(): string
    {
        $icons = [
            self::TYPE_ASSIGNMENT_CREATED => 'assignment',
            self::TYPE_ASSIGNMENT_PUBLISHED => 'publish',
            self::TYPE_ASSIGNMENT_DUE_SOON => 'schedule',
            self::TYPE_ASSIGNMENT_OVERDUE => 'warning',
            self::TYPE_SUBMISSION_REVIEWED => 'check_circle',
            self::TYPE_FEEDBACK_RECEIVED => 'feedback',
            self::TYPE_HASANAT_AWARDED => 'star',
            self::TYPE_ACHIEVEMENT_UNLOCKED => 'trophy'
        ];

        return $icons[$this->type] ?? 'notifications';
    }
    
    /**
     * Get the notification color based on type.
     *
     * @return string
     */
    public function getColor(): string
    {
        $colors = [
            self::TYPE_ASSIGNMENT_CREATED => 'blue',
            self::TYPE_ASSIGNMENT_PUBLISHED => 'green',
            self::TYPE_ASSIGNMENT_DUE_SOON => 'orange',
            self::TYPE_ASSIGNMENT_OVERDUE => 'red',
            self::TYPE_SUBMISSION_REVIEWED => 'green',
            self::TYPE_FEEDBACK_RECEIVED => 'purple',
            self::TYPE_HASANAT_AWARDED => 'gold',
            self::TYPE_ACHIEVEMENT_UNLOCKED => 'gold'
        ];

        return $colors[$this->type] ?? 'gray';
    }
    
    /**
     * Scope to get only unread notifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
    
    /**
     * Scope to get only read notifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }
    
    /**
     * Scope to filter notifications by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type Notification type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * Convert notification to frontend array format.
     *
     * @return array
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'action_url' => $this->getActionUrl(),
            'read' => $this->read(),
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'data' => $this->data
        ];
    }
}