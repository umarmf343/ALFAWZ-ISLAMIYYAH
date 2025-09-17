<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Hotspot extends Model
{
    use HasFactory;

    // Hotspot types
    const TYPE_AUDIO = 'audio';
    const TYPE_TEXT = 'text';
    const TYPE_VIDEO = 'video';
    const TYPE_IMAGE = 'image';
    const TYPE_TOOLTIP = 'tooltip';
    const TYPE_INTERACTIVE = 'interactive';

    // Animation types
    const ANIMATION_PULSE = 'pulse';
    const ANIMATION_BOUNCE = 'bounce';
    const ANIMATION_GLOW = 'glow';
    const ANIMATION_SHAKE = 'shake';
    const ANIMATION_NONE = 'none';

    // Available hotspot types
    public static array $types = [
        self::TYPE_AUDIO,
        self::TYPE_TEXT,
        self::TYPE_VIDEO,
        self::TYPE_IMAGE,
        self::TYPE_TOOLTIP,
        self::TYPE_INTERACTIVE
    ];

    // Available animations
    public static array $animations = [
        self::ANIMATION_PULSE,
        self::ANIMATION_BOUNCE,
        self::ANIMATION_GLOW,
        self::ANIMATION_SHAKE,
        self::ANIMATION_NONE
    ];

    protected $fillable = [
        'assignment_id',
        'x_coordinate',
        'y_coordinate',
        'width',
        'height',
        'type',
        'title',
        'content',
        'media_url',
        'thumbnail_url',
        'is_required',
        'play_count',
        'auto_play',
        'duration_seconds',
        'icon',
        'color',
        'animation',
        'order_index',
        'group_name',
        'is_active'
    ];

    protected $casts = [
        'x_coordinate' => 'decimal:4',
        'y_coordinate' => 'decimal:4',
        'is_required' => 'boolean',
        'auto_play' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Get the assignment this hotspot belongs to.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get interactions for this hotspot.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(HotspotInteraction::class);
    }

    /**
     * Check if hotspot is audio type.
     */
    public function isAudio(): bool
    {
        return $this->type === self::TYPE_AUDIO;
    }

    /**
     * Check if hotspot is video type.
     */
    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    /**
     * Check if hotspot is interactive type.
     */
    public function isInteractive(): bool
    {
        return $this->type === self::TYPE_INTERACTIVE;
    }

    /**
     * Check if hotspot has media content.
     */
    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }

    /**
     * Get full URL for media file.
     */
    public function getMediaUrlAttribute(): ?string
    {
        return $this->attributes['media_url'] ? Storage::url($this->attributes['media_url']) : null;
    }

    /**
     * Get full URL for thumbnail file.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->attributes['thumbnail_url'] ? Storage::url($this->attributes['thumbnail_url']) : null;
    }

    /**
     * Generate a unique slug for the hotspot.
     */
    public function generateSlug(): string
    {
        $baseSlug = Str::slug($this->title ?: 'hotspot');
        return $baseSlug . '-' . $this->id;
    }

    /**
     * Get CSS styles for hotspot positioning.
     */
    public function getPositionStyles(): array
    {
        return [
            'left' => $this->x_coordinate . '%',
            'top' => $this->y_coordinate . '%',
            'width' => $this->width . 'px',
            'height' => $this->height . 'px'
        ];
    }

    /**
     * Increment play count when hotspot is interacted with.
     */
    public function recordInteraction(User $user = null): void
    {
        $this->increment('play_count');
        
        // Record detailed interaction if user is provided
        if ($user) {
            $this->interactions()->create([
                'user_id' => $user->id,
                'interaction_type' => 'play',
                'timestamp' => now()
            ]);
        }
    }

    /**
     * Get interaction count for a specific user.
     */
    public function getInteractionCount(User $user): int
    {
        return $this->interactions()->where('user_id', $user->id)->count();
    }

    /**
     * Check if user has interacted with this hotspot.
     */
    public function hasUserInteracted(User $user): bool
    {
        return $this->interactions()->where('user_id', $user->id)->exists();
    }

    /**
     * Get hotspot data for frontend consumption.
     */
    public function toFrontendArray(User $user = null): array
    {
        $data = [
            'id' => $this->id,
            'x' => $this->x_coordinate,
            'y' => $this->y_coordinate,
            'width' => $this->width,
            'height' => $this->height,
            'type' => $this->type,
            'title' => $this->title,
            'content' => $this->content,
            'media_url' => $this->media_url,
            'thumbnail_url' => $this->thumbnail_url,
            'is_required' => $this->is_required,
            'auto_play' => $this->auto_play,
            'duration' => $this->duration_seconds,
            'icon' => $this->icon ?: $this->getDefaultIcon(),
            'color' => $this->color ?: $this->getDefaultColor(),
            'animation' => $this->animation ?: self::ANIMATION_PULSE,
            'order_index' => $this->order_index,
            'play_count' => $this->play_count,
            'group_name' => $this->group_name,
            'is_active' => $this->is_active,
            'slug' => $this->generateSlug(),
            'position_styles' => $this->getPositionStyles()
        ];
        
        // Add user-specific data if user is provided
        if ($user) {
            $data['user_interaction_count'] = $this->getInteractionCount($user);
            $data['has_user_interacted'] = $this->hasUserInteracted($user);
        }
        
        return $data;
    }

    /**
     * Get default icon based on hotspot type.
     */
    public function getDefaultIcon(): string
    {
        return match($this->type) {
            self::TYPE_AUDIO => '🔊',
            self::TYPE_VIDEO => '🎥',
            self::TYPE_IMAGE => '🖼️',
            self::TYPE_TEXT => '📝',
            self::TYPE_TOOLTIP => '💡',
            self::TYPE_INTERACTIVE => '🎯',
            default => '📍'
        };
    }

    /**
     * Get default color based on hotspot type.
     */
    public function getDefaultColor(): string
    {
        return match($this->type) {
            self::TYPE_AUDIO => '#8B0000',      // Maroon
            self::TYPE_VIDEO => '#DAA520',      // Gold
            self::TYPE_IMAGE => '#F5F5DC',      // Milk/Beige
            self::TYPE_TEXT => '#4A4A4A',       // Dark Gray
            self::TYPE_TOOLTIP => '#FFD700',    // Gold
            self::TYPE_INTERACTIVE => '#8B0000', // Maroon
            default => '#8B0000'                // Default Maroon
        };
    }

    /**
     * Scope for active hotspots.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered hotspots.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index')->orderBy('created_at');
    }

    /**
     * Scope for hotspots by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for required hotspots.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for hotspots with media.
     */
    public function scopeWithMedia($query)
    {
        return $query->whereNotNull('media_url');
    }

    /**
     * Scope for hotspots in a specific group.
     */
    public function scopeInGroup($query, string $groupName)
    {
        return $query->where('group_name', $groupName);
    }

    /**
     * Scope for auto-play hotspots.
     */
    public function scopeAutoPlay($query)
    {
        return $query->where('auto_play', true);
    }
}