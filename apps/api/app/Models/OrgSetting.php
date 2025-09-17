<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class OrgSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("org_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key and clear cache.
     */
    public static function set(string $key, $value, string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );

        Cache::forget("org_setting_{$key}");
        
        return $setting;
    }

    /**
     * Get Tajweed-specific settings as an array.
     */
    public static function getTajweedSettings(): array
    {
        return [
            'default_enabled' => self::get('tajweed_default_enabled', true),
            'daily_limit_per_user' => self::get('tajweed_daily_limit_per_user', 50),
            'max_audio_duration' => self::get('tajweed_max_audio_duration', 300),
            'retention_days' => self::get('tajweed_retention_days', 30),
        ];
    }

    /**
     * Update multiple Tajweed settings at once.
     */
    public static function updateTajweedSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $fullKey = str_starts_with($key, 'tajweed_') ? $key : "tajweed_{$key}";
            self::set($fullKey, $value);
        }
    }

    /**
     * Check if Tajweed analysis is enabled organization-wide.
     */
    public static function isTajweedEnabled(): bool
    {
        return (bool) self::get('tajweed_default_enabled', true);
    }

    /**
     * Get the daily limit for Tajweed analyses per user.
     */
    public static function getTajweedDailyLimit(): int
    {
        return (int) self::get('tajweed_daily_limit_per_user', 50);
    }

    /**
     * Get the maximum audio duration for Tajweed analysis.
     */
    public static function getMaxAudioDuration(): int
    {
        return (int) self::get('tajweed_max_audio_duration', 300);
    }

    /**
     * Get the retention period for audio files in days.
     */
    public static function getRetentionDays(): int
    {
        return (int) self::get('tajweed_retention_days', 30);
    }
}