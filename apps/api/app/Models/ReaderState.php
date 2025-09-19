<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReaderState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_surah',
        'current_ayah',
        'font_size',
        'translation_enabled',
        'audio_enabled',
        'reciter_id',
    ];

    protected $casts = [
        'current_surah' => 'integer',
        'current_ayah' => 'integer',
        'translation_enabled' => 'boolean',
        'audio_enabled' => 'boolean',
        'reciter_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
