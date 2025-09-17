<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhisperJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'recitation_id','status','result_json','error'
    ];

    protected $casts = [
        'result_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants for job processing states.
     */
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    /**
     * recitation returns the input media row.
     * Each whisper job belongs to one recitation.
     */
    public function recitation(): BelongsTo
    {
        return $this->belongsTo(Recitation::class);
    }

    /**
     * Scope to get jobs by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get failed jobs within specified hours.
     */
    public function scopeFailedWithinHours($query, int $hours = 24)
    {
        return $query->where('status', self::STATUS_FAILED)
                    ->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if the job is completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    /**
     * Check if the job has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the job is still processing.
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING]);
    }

    /**
     * Get the overall score from analysis results.
     */
    public function getOverallScore(): ?int
    {
        return $this->result_json['score'] ?? null;
    }

    /**
     * Get the subscores from analysis results.
     */
    public function getSubscores(): ?array
    {
        return $this->result_json['subscores'] ?? null;
    }

    /**
     * Get the mistakes from analysis results.
     */
    public function getMistakes(): array
    {
        return $this->result_json['mistakes'] ?? [];
    }
}