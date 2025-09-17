<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    use HasFactory;

    /**
     * Status constants for submissions.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_GRADED = 'graded';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_COMPLETED = 'completed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assignment_id',
        'student_id',
        'status',
        'score',
        'rubric_json',
        'audio_s3_url',
        'text_response',
        'audio_url',
        'audio_duration',
        'audio_format',
        'completion_percentage',
        'hotspot_interactions',
        'attempts_count',
        'hasanat_earned',
        'accuracy_score',
        'fluency_score',
        'overall_score',
        'ai_analysis',
        'transcription',
        'tajweed_feedback',
        'started_at',
        'submitted_at',
        'reviewed_at',
        'time_spent_seconds',
        'requires_review',
        'teacher_notes',
        'teacher_rating'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'rubric_json' => 'array',
        'hotspot_interactions' => 'array',
        'ai_analysis' => 'array',
        'tajweed_feedback' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'requires_review' => 'boolean',
        'accuracy_score' => 'decimal:2',
        'fluency_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the assignment this submission belongs to.
     *
     * @return BelongsTo
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the student who made this submission.
     *
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get all feedback for this submission.
     *
     * @return HasMany
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Check if submission is pending grading.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if submission has been graded.
     *
     * @return bool
     */
    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    /**
     * Mark submission as graded with score.
     *
     * @param int $score
     * @param array|null $rubric
     * @return bool
     */
    public function grade(int $score, ?array $rubric = null): bool
    {
        return $this->update([
            'status' => self::STATUS_GRADED,
            'score' => $score,
            'rubric_json' => $rubric,
        ]);
    }

    /**
     * Check if submission has audio recording.
     *
     * @return bool
     */
    public function hasAudio(): bool
    {
        return !empty($this->audio_s3_url) || !empty($this->audio_url);
    }

    /**
     * Check if submission is completed.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_REVIEWED, self::STATUS_COMPLETED]);
    }

    /**
     * Check if submission is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if submission needs review.
     */
    public function needsReview(): bool
    {
        return $this->requires_review && $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Get full URL for audio file.
     */
    public function getAudioUrlAttribute(): ?string
    {
        if ($this->attributes['audio_url']) {
            return Storage::url($this->attributes['audio_url']);
        }
        return $this->attributes['audio_s3_url'] ?? null;
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
     * Get formatted time spent on assignment.
     */
    public function getFormattedTimeSpent(): string
    {
        $minutes = floor($this->time_spent_seconds / 60);
        $seconds = $this->time_spent_seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Calculate completion percentage based on requirements.
     */
    public function calculateCompletionPercentage(): int
    {
        $totalRequirements = 0;
        $completedRequirements = 0;
        
        // Text response requirement
        if (in_array($this->assignment->type, ['reading', 'memorization'])) {
            $totalRequirements++;
            if (!empty($this->text_response)) {
                $completedRequirements++;
            }
        }
        
        // Audio requirement
        if ($this->assignment->requires_audio ?? false) {
            $totalRequirements++;
            if ($this->hasAudio()) {
                $completedRequirements++;
            }
        }
        
        // Required hotspots (if assignment has hotspots relation)
        if (method_exists($this->assignment, 'hotspots')) {
            $requiredHotspots = $this->assignment->hotspots()->where('is_required', true)->count();
            if ($requiredHotspots > 0) {
                $totalRequirements += $requiredHotspots;
                $interactedHotspots = $this->hotspot_interactions ? count($this->hotspot_interactions) : 0;
                $completedRequirements += min($interactedHotspots, $requiredHotspots);
            }
        }
        
        return $totalRequirements > 0 ? (int) (($completedRequirements / $totalRequirements) * 100) : 0;
    }

    /**
     * Start the submission (track start time).
     */
    public function start(): void
    {
        if (!$this->started_at) {
            $this->update([
                'status' => self::STATUS_IN_PROGRESS,
                'started_at' => now()
            ]);
        }
    }

    /**
     * Submit the assignment for review.
     */
    public function submit(): bool
    {
        $completionPercentage = $this->calculateCompletionPercentage();
        
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'completion_percentage' => $completionPercentage,
            'requires_review' => true
        ]);
        
        // Award hasanat based on completion
        $this->awardHasanat();
        
        return true;
    }

    /**
     * Award hasanat based on completion and quality.
     */
    public function awardHasanat(): void
    {
        $baseHasanat = $this->assignment->expected_hasanat ?? 10;
        $completionBonus = (int) ($baseHasanat * ($this->completion_percentage / 100));
        
        // Quality bonus based on scores
        $qualityBonus = 0;
        if ($this->overall_score) {
            $qualityBonus = (int) ($baseHasanat * 0.2 * ($this->overall_score / 100));
        }
        
        // Hotspot interaction bonus
        $hotspotBonus = $this->hotspot_interactions ? count($this->hotspot_interactions) * 5 : 0;
        
        $totalHasanat = $completionBonus + $qualityBonus + $hotspotBonus;
        
        $this->update(['hasanat_earned' => $totalHasanat]);
        
        // Update student's total hasanat if method exists
        if (method_exists($this->student, 'increment')) {
            $this->student->increment('total_hasanat', $totalHasanat);
        }
        
        // Update leaderboard entry
        $this->updateLeaderboardEntry($totalHasanat);
    }

    /**
     * Record hotspot interaction.
     */
    public function recordHotspotInteraction(int $hotspotId): void
    {
        $interactions = $this->hotspot_interactions ?: [];
        
        if (!in_array($hotspotId, $interactions)) {
            $interactions[] = $hotspotId;
            $this->update(['hotspot_interactions' => $interactions]);
            
            // Update completion percentage
            $this->update(['completion_percentage' => $this->calculateCompletionPercentage()]);
        }
    }

    /**
     * Process AI analysis from Whisper API.
     */
    public function processAIAnalysis(array $whisperResponse): void
    {
        $this->update([
            'ai_analysis' => $whisperResponse,
            'transcription' => $whisperResponse['text'] ?? null,
            'tajweed_feedback' => $this->extractTajweedFeedback($whisperResponse)
        ]);
        
        // Calculate AI-based scores
        $this->calculateAIScores($whisperResponse);
    }

    /**
     * Extract tajweed feedback from AI analysis.
     */
    private function extractTajweedFeedback(array $analysis): array
    {
        // This would contain logic to analyze pronunciation, tajweed rules, etc.
        // For now, return a basic structure
        return [
            'pronunciation_accuracy' => $analysis['pronunciation_score'] ?? null,
            'tajweed_rules' => $analysis['tajweed_analysis'] ?? [],
            'suggestions' => $analysis['improvement_suggestions'] ?? []
        ];
    }

    /**
     * Calculate AI-based scores.
     */
    private function calculateAIScores(array $analysis): void
    {
        $accuracyScore = $analysis['accuracy_score'] ?? null;
        $fluencyScore = $analysis['fluency_score'] ?? null;
        
        $overallScore = null;
        if ($accuracyScore && $fluencyScore) {
            $overallScore = ($accuracyScore + $fluencyScore) / 2;
        }
        
        $this->update([
            'accuracy_score' => $accuracyScore,
            'fluency_score' => $fluencyScore,
            'overall_score' => $overallScore
        ]);
    }

    /**
     * Mark as reviewed by teacher.
     */
    public function markAsReviewed(): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWED,
            'reviewed_at' => now(),
            'requires_review' => false
        ]);
    }

    /**
     * Get submission data for frontend.
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'status' => $this->status,
            'completion_percentage' => $this->completion_percentage,
            'hasanat_earned' => $this->hasanat_earned,
            'audio_url' => $this->audio_url,
            'audio_duration' => $this->getFormattedDuration(),
            'time_spent' => $this->getFormattedTimeSpent(),
            'overall_score' => $this->overall_score,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'hotspot_interactions' => $this->hotspot_interactions ?: [],
            'feedback_count' => $this->feedback()->count(),
            'needs_review' => $this->needsReview()
        ];
    }

    /**
     * Get rubric score for specific criteria.
     *
     * @param string $criteria
     * @return int|null
     */
    public function getRubricScore(string $criteria): ?int
    {
        return $this->rubric_json[$criteria] ?? null;
    }

    /**
     * Set rubric score for specific criteria.
     *
     * @param string $criteria
     * @param int $score
     * @return void
     */
    public function setRubricScore(string $criteria, int $score): void
    {
        $rubric = $this->rubric_json ?? [];
        $rubric[$criteria] = $score;
        $this->update(['rubric_json' => $rubric]);
    }

    /**
     * Check if submission is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        if (!$this->assignment->due_at) {
            return false;
        }
        
        return $this->created_at->isAfter($this->assignment->due_at);
    }

    /**
     * Scope submissions by status.
     *
     * @param Builder $query
     * @param string $status
     * @return Builder
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pending submissions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope graded submissions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeGraded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    /**
     * Scope submissions by student.
     *
     * @param Builder $query
     * @param int $studentId
     * @return Builder
     */
    public function scopeByStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for submissions needing review.
     */
    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('requires_review', true)->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Scope for completed submissions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_REVIEWED, self::STATUS_COMPLETED]);
    }

    /**
     * Scope for in progress submissions.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Update leaderboard entry for the student.
     *
     * @param int $hasanatEarned
     * @return void
     */
    private function updateLeaderboardEntry(int $hasanatEarned): void
    {
        if ($hasanatEarned <= 0) {
            return;
        }

        // Get or create leaderboard entry for this week
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $leaderboardEntry = \App\Models\LeaderboardEntry::firstOrCreate(
            [
                'user_id' => $this->student_id,
                'period_start' => $startOfWeek,
                'period_end' => $endOfWeek,
                'period_type' => 'weekly'
            ],
            [
                'total_hasanat' => 0,
                'activities_completed' => 0,
                'streak_days' => 0,
                'last_activity_at' => now()
            ]
        );

        // Update the entry
        $leaderboardEntry->increment('total_hasanat', $hasanatEarned);
        $leaderboardEntry->increment('activities_completed');
        $leaderboardEntry->update(['last_activity_at' => now()]);

        // Update streak if this is a new day
        $lastActivity = $leaderboardEntry->last_activity_at;
        if (!$lastActivity || $lastActivity->format('Y-m-d') !== now()->format('Y-m-d')) {
            // Check if yesterday had activity to maintain streak
            $yesterday = now()->subDay();
            $yesterdayEntry = \App\Models\LeaderboardEntry::where('user_id', $this->student_id)
                ->where('period_type', 'weekly')
                ->where('last_activity_at', '>=', $yesterday->startOfDay())
                ->where('last_activity_at', '<=', $yesterday->endOfDay())
                ->exists();

            if ($yesterdayEntry || $leaderboardEntry->streak_days === 0) {
                $leaderboardEntry->increment('streak_days');
            } else {
                $leaderboardEntry->update(['streak_days' => 1]);
            }
        }
    }
}