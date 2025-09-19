<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks a student's listening progress for Quran surah audio lessons.
 */
class AudioProgress extends Model
{
    use HasFactory;

    protected $table = 'audio_progress';

    /**
     * Attributes that can be mass-assigned.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'surah_id',
        'surah_name',
        'position_seconds',
        'duration_seconds',
    ];

    /**
     * Casts for numeric values.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position_seconds' => 'float',
        'duration_seconds' => 'float',
    ];

    /**
     * The student that owns the progress record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
