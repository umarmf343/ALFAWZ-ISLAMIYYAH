<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class HotspotInteraction extends Model
{
    use HasFactory;

    // Interaction types
    const TYPE_PLAY = 'play';
    const TYPE_PAUSE = 'pause';
    const TYPE_COMPLETE = 'complete';
    const TYPE_CLICK = 'click';
    const TYPE_HOVER = 'hover';
    const TYPE_VIEW = 'view';

    // Available interaction types
    public static array $types = [
        self::TYPE_PLAY,
        self::TYPE_PAUSE,
        self::TYPE_COMPLETE,
        self::TYPE_CLICK,
        self::TYPE_HOVER,
        self::TYPE_VIEW
    ];

    protected $fillable = [
        'hotspot_id',
        'user_id',
        'interaction_type',
        'duration_seconds',
        'completion_percentage',
        'metadata',
        'timestamp'
    ];

    protected $casts = [
        'metadata' => 'array',
        'timestamp' => 'datetime',
        'completion_percentage' => 'decimal:2'
    ];

    /**
     * Get the hotspot this interaction belongs to.
     */
    public function hotspot(): BelongsTo
    {
        return $this->belongsTo(Hotspot::class);
    }

    /**
     * Get the user who made this interaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if interaction is a play action.
     */
    public function isPlay(): bool
    {
        return $this->interaction_type === self::TYPE_PLAY;
    }

    /**
     * Check if interaction is a completion action.
     */
    public function isComplete(): bool
    {
        return $this->interaction_type === self::TYPE_COMPLETE;
    }

    /**
     * Get interaction data for frontend consumption.
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'hotspot_id' => $this->hotspot_id,
            'user_id' => $this->user_id,
            'interaction_type' => $this->interaction_type,
            'duration_seconds' => $this->duration_seconds,
            'completion_percentage' => $this->completion_percentage,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->toISOString(),
            'created_at' => $this->created_at->toISOString()
        ];
    }

    /**
     * Scope for interactions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for completed interactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('interaction_type', self::TYPE_COMPLETE)
                    ->orWhere('completion_percentage', '>=', 100);
    }

    /**
     * Scope for interactions within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope for recent interactions.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }

    /**
     * Get interaction statistics for a hotspot.
     */
    public static function getHotspotStats(int $hotspotId): array
    {
        $stats = self::where('hotspot_id', $hotspotId)
            ->select(
                DB::raw('COUNT(*) as total_interactions'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('AVG(duration_seconds) as avg_duration'),
                DB::raw('AVG(completion_percentage) as avg_completion'),
                DB::raw('COUNT(CASE WHEN interaction_type = "' . self::TYPE_COMPLETE . '" THEN 1 END) as completions')
            )
            ->first();

        return [
            'total_interactions' => $stats->total_interactions ?? 0,
            'unique_users' => $stats->unique_users ?? 0,
            'average_duration' => round($stats->avg_duration ?? 0, 2),
            'average_completion' => round($stats->avg_completion ?? 0, 2),
            'completion_count' => $stats->completions ?? 0,
            'completion_rate' => $stats->unique_users > 0 
                ? round(($stats->completions / $stats->unique_users) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get user interaction summary for a hotspot.
     */
    public static function getUserHotspotSummary(int $userId, int $hotspotId): array
    {
        $interactions = self::where('user_id', $userId)
            ->where('hotspot_id', $hotspotId)
            ->orderBy('timestamp', 'desc')
            ->get();

        $totalDuration = $interactions->sum('duration_seconds');
        $maxCompletion = $interactions->max('completion_percentage') ?? 0;
        $interactionCount = $interactions->count();
        $hasCompleted = $interactions->where('interaction_type', self::TYPE_COMPLETE)->isNotEmpty();
        $lastInteraction = $interactions->first();

        return [
            'interaction_count' => $interactionCount,
            'total_duration' => $totalDuration,
            'max_completion_percentage' => $maxCompletion,
            'has_completed' => $hasCompleted,
            'last_interaction_at' => $lastInteraction?->timestamp?->toISOString(),
            'last_interaction_type' => $lastInteraction?->interaction_type
        ];
    }
}