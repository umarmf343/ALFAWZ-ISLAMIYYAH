<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'scope',
        'period',
        'data_json',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data_json' => 'array',
    ];

    /**
     * Get the latest analytics snapshot for a given scope and period.
     * Uses caching for performance optimization.
     *
     * @param string $scope The analytics scope (global, class, region)
     * @param string $period The time period (daily, weekly, monthly)
     * @return AnalyticsSnapshot|null
     */
    public static function getLatest(string $scope = 'global', string $period = 'weekly'): ?self
    {
        $cacheKey = "analytics:{$scope}:{$period}";
        
        return Cache::remember($cacheKey, 3600, function () use ($scope, $period) {
            return self::where('scope', $scope)
                      ->where('period', $period)
                      ->latest()
                      ->first();
        });
    }

    /**
     * Clear cache for analytics snapshots.
     * Called when new snapshots are created.
     *
     * @param string|null $scope Optional scope to clear specific cache
     * @param string|null $period Optional period to clear specific cache
     */
    public static function clearCache(?string $scope = null, ?string $period = null): void
    {
        if ($scope && $period) {
            Cache::forget("analytics:{$scope}:{$period}");
        } else {
            // Clear all analytics cache
            $scopes = ['global', 'class', 'region'];
            $periods = ['daily', 'weekly', 'monthly'];
            
            foreach ($scopes as $s) {
                foreach ($periods as $p) {
                    Cache::forget("analytics:{$s}:{$p}");
                }
            }
        }
    }

    /**
     * Boot method to clear cache when model is saved.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($model) {
            $model->clearCache($model->scope, $model->period);
        });
    }
}