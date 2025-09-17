<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'submission_id',
        'teacher_id',
        'feedback_text',
        'feedback_type',
        'feedback_area',
        'accuracy_score',
        'fluency_score',
        'tajweed_score',
        'overall_score',
        'recommendations',
        'audio_feedback_url',
        'audio_duration',
        'is_visible_to_student',
        'status',
        'ai_confidence',
        'ai_generated',
        'priority',
        'note',
        'audio_s3_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recommendations' => 'array',
        'is_visible_to_student' => 'boolean',
        'ai_generated' => 'boolean',
        'accuracy_score' => 'decimal:2',
        'fluency_score' => 'decimal:2',
        'tajweed_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Feedback types
    const TYPE_GENERAL = 'general';
    const TYPE_PRONUNCIATION = 'pronunciation';
    const TYPE_TAJWEED = 'tajweed';
    const TYPE_FLUENCY = 'fluency';
    const TYPE_ACCURACY = 'accuracy';
    const TYPE_AI_ANALYSIS = 'ai_analysis';

    // Feedback areas
    const AREA_OVERALL = 'overall';
    const AREA_SPECIFIC_VERSE = 'specific_verse';
    const AREA_WORD_LEVEL = 'word_level';
    const AREA_LETTER_LEVEL = 'letter_level';

    // Status
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the submission this feedback belongs to.
     *
     * @return BelongsTo
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the teacher who provided this feedback.
     *
     * @return BelongsTo
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Check if feedback has audio recording.
     *
     * @return bool
     */
    public function hasAudio(): bool
    {
        return !empty($this->audio_s3_url) || !empty($this->audio_feedback_url);
    }

    /**
     * Check if feedback has written note.
     *
     * @return bool
     */
    public function hasNote(): bool
    {
        return !empty($this->note) || !empty($this->feedback_text);
    }

    /**
     * Check if feedback is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if feedback is AI generated.
     */
    public function isAIGenerated(): bool
    {
        return $this->ai_generated;
    }

    /**
     * Check if feedback is visible to student.
     */
    public function isVisibleToStudent(): bool
    {
        return $this->is_visible_to_student && $this->isPublished();
    }

    /**
     * Get full URL for audio feedback file.
     */
    public function getAudioFeedbackUrlAttribute(): ?string
    {
        return $this->attributes['audio_feedback_url'] ? Storage::url($this->attributes['audio_feedback_url']) : null;
    }

    /**
     * Get formatted audio duration.
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->audio_duration) return null;
        
        $minutes = floor($this->audio_duration / 60);
        $seconds = $this->audio_duration % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get feedback priority color for UI.
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_URGENT => '#dc2626', // red-600
            self::PRIORITY_HIGH => '#ea580c',   // orange-600
            self::PRIORITY_MEDIUM => '#ca8a04', // yellow-600
            self::PRIORITY_LOW => '#16a34a',    // green-600
            default => '#6b7280'               // gray-500
        };
    }

    /**
     * Get feedback type display name.
     */
    public function getTypeDisplayName(): string
    {
        return match($this->feedback_type) {
            self::TYPE_GENERAL => 'General Feedback',
            self::TYPE_PRONUNCIATION => 'Pronunciation',
            self::TYPE_TAJWEED => 'Tajweed Rules',
            self::TYPE_FLUENCY => 'Fluency',
            self::TYPE_ACCURACY => 'Accuracy',
            self::TYPE_AI_ANALYSIS => 'AI Analysis',
            default => 'Feedback'
        };
    }

    /**
     * Get overall score based on individual scores.
     */
    public function calculateOverallScore(): ?float
    {
        $scores = array_filter([
            $this->accuracy_score,
            $this->fluency_score,
            $this->tajweed_score
        ]);
        
        if (empty($scores)) {
            return null;
        }
        
        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Update overall score based on individual scores.
     */
    public function updateOverallScore(): void
    {
        $overallScore = $this->calculateOverallScore();
        if ($overallScore !== null) {
            $this->update(['overall_score' => $overallScore]);
        }
    }

    /**
     * Publish feedback (make it visible to student).
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => self::STATUS_PUBLISHED,
            'is_visible_to_student' => true
        ]);
    }

    /**
     * Archive feedback.
     */
    public function archive(): bool
    {
        return $this->update([
            'status' => self::STATUS_ARCHIVED,
            'is_visible_to_student' => false
        ]);
    }

    /**
     * Create AI-generated feedback from analysis.
     */
    public static function createFromAIAnalysis(Submission $submission, array $analysis): self
    {
        $feedback = self::create([
            'submission_id' => $submission->id,
            'feedback_text' => $analysis['feedback_text'] ?? 'AI analysis completed',
            'feedback_type' => self::TYPE_AI_ANALYSIS,
            'feedback_area' => self::AREA_OVERALL,
            'accuracy_score' => $analysis['accuracy_score'] ?? null,
            'fluency_score' => $analysis['fluency_score'] ?? null,
            'tajweed_score' => $analysis['tajweed_score'] ?? null,
            'recommendations' => $analysis['recommendations'] ?? [],
            'ai_generated' => true,
            'ai_confidence' => $analysis['confidence'] ?? null,
            'status' => self::STATUS_PUBLISHED,
            'is_visible_to_student' => true,
            'priority' => self::PRIORITY_MEDIUM
        ]);
        
        $feedback->updateOverallScore();
        
        return $feedback;
    }

    /**
     * Add teacher recommendation.
     */
    public function addRecommendation(string $recommendation): void
    {
        $recommendations = $this->recommendations ?: [];
        $recommendations[] = [
            'text' => $recommendation,
            'added_at' => now()->toISOString(),
            'type' => 'teacher'
        ];
        
        $this->update(['recommendations' => $recommendations]);
    }

    /**
     * Get formatted recommendations for display.
     */
    public function getFormattedRecommendations(): array
    {
        if (!$this->recommendations) {
            return [];
        }
        
        return collect($this->recommendations)->map(function ($rec) {
            return [
                'text' => $rec['text'] ?? $rec,
                'type' => $rec['type'] ?? 'general',
                'added_at' => $rec['added_at'] ?? null
            ];
        })->toArray();
    }

    /**
     * Get feedback content summary.
     *
     * @return string
     */
    public function getSummary(): string
    {
        $parts = [];
        
        if ($this->hasNote()) {
            $parts[] = 'Written feedback';
        }
        
        if ($this->hasAudio()) {
            $parts[] = 'Audio feedback';
        }
        
        return empty($parts) ? 'No feedback content' : implode(' + ', $parts);
    }

    /**
     * Get truncated note for preview.
     *
     * @param int $length
     * @return string
     */
    public function getPreviewNote(int $length = 100): string
    {
        if (!$this->hasNote()) {
            return '';
        }
        
        $note = $this->note ?: $this->feedback_text;
        return strlen($note) > $length 
            ? substr($note, 0, $length) . '...'
            : $note;
    }

    /**
     * Check if feedback is comprehensive (has both note and audio).
     *
     * @return bool
     */
    public function isComprehensive(): bool
    {
        return $this->hasNote() && $this->hasAudio();
    }

    /**
     * Get feedback data for frontend.
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'teacher_name' => $this->teacher?->name ?? 'AI Assistant',
            'feedback_text' => $this->feedback_text ?: $this->note,
            'feedback_type' => $this->feedback_type,
            'type_display_name' => $this->getTypeDisplayName(),
            'feedback_area' => $this->feedback_area,
            'scores' => [
                'accuracy' => $this->accuracy_score,
                'fluency' => $this->fluency_score,
                'tajweed' => $this->tajweed_score,
                'overall' => $this->overall_score
            ],
            'recommendations' => $this->getFormattedRecommendations(),
            'audio_feedback_url' => $this->audio_feedback_url ?: $this->audio_s3_url,
            'audio_duration' => $this->getFormattedDuration(),
            'priority' => $this->priority,
            'priority_color' => $this->getPriorityColor(),
            'ai_generated' => $this->ai_generated,
            'ai_confidence' => $this->ai_confidence,
            'created_at' => $this->created_at->toISOString(),
            'is_published' => $this->isPublished()
        ];
    }

    /**
     * Scope for published feedback.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope for visible feedback (published and visible to student).
     */
    public function scopeVisibleToStudent($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->where('is_visible_to_student', true);
    }

    /**
     * Scope for AI-generated feedback.
     */
    public function scopeAIGenerated($query)
    {
        return $query->where('ai_generated', true);
    }

    /**
     * Scope for teacher feedback.
     */
    public function scopeTeacherGenerated($query)
    {
        return $query->where('ai_generated', false)->whereNotNull('teacher_id');
    }

    /**
     * Scope for feedback by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('feedback_type', $type);
    }

    /**
     * Scope for feedback by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for high priority feedback.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope feedback by teacher.
     *
     * @param Builder $query
     * @param int $teacherId
     * @return Builder
     */
    public function scopeByTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope feedback with audio.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithAudio(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNotNull('audio_s3_url')
              ->orWhereNotNull('audio_feedback_url');
        });
    }

    /**
     * Scope feedback with notes.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithNotes(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNotNull('note')
              ->orWhereNotNull('feedback_text');
        });
    }

    /**
     * Scope recent feedback.
     *
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}