<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FlaggedContent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'content_type',
        'content_id',
        'flagged_by',
        'reason',
        'status',
    ];

    /**
     * Status constants for flagged content.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_REMOVED = 'removed';

    /**
     * Get all available status options.
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_REVIEWED,
            self::STATUS_REMOVED,
        ];
    }

    /**
     * Get the flagged content (polymorphic relationship).
     * Supports journal entries, group posts, and other content types.
     *
     * @return MorphTo
     */
    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who flagged this content.
     *
     * @return BelongsTo
     */
    public function flaggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    /**
     * Scope to filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending flagged content.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Mark content as reviewed.
     *
     * @return bool
     */
    public function markAsReviewed(): bool
    {
        return $this->update(['status' => self::STATUS_REVIEWED]);
    }

    /**
     * Mark content as removed and delete the actual content.
     *
     * @return bool
     */
    public function markAsRemoved(): bool
    {
        $this->update(['status' => self::STATUS_REMOVED]);
        
        // Delete the actual flagged content
        if ($this->content) {
            $this->content->delete();
        }
        
        return true;
    }

    /**
     * Check if content is pending review.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if content has been reviewed.
     *
     * @return bool
     */
    public function isReviewed(): bool
    {
        return $this->status === self::STATUS_REVIEWED;
    }

    /**
     * Check if content has been removed.
     *
     * @return bool
     */
    public function isRemoved(): bool
    {
        return $this->status === self::STATUS_REMOVED;
    }
}