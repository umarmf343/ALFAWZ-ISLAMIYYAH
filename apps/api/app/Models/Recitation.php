<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recitation extends Model
{
    protected $fillable = [
        'user_id',
        'surah',
        'from_ayah',
        'to_ayah',
        's3_key',
        'mime',
        'expected_tokens',
        'duration_seconds',
        'tajweed_enabled',
    ];

    protected $casts = [
        'surah' => 'integer',
        'from_ayah' => 'integer',
        'to_ayah' => 'integer',
        'duration_seconds' => 'integer',
        'tajweed_enabled' => 'boolean',
        'expected_tokens' => 'array',
    ];

    /**
     * whisperJob returns the associated analysis task row.
     * Each recitation has one whisper analysis job.
     */
    public function whisperJob(): HasOne
    {
        return $this->hasOne(WhisperJob::class);
    }

    /**
     * user returns the user who submitted this recitation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}