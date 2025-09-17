<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Notifications;

use App\Models\User;
use App\Models\MemorizationPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class MemorizationProgressUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $student;
    public $plan;
    public $surahId;
    public $ayahId;
    public $confidenceScore;
    public $timestamp;

    /**
     * Create a new notification instance.
     *
     * @param User $student The student who made progress
     * @param MemorizationPlan $plan The memorization plan
     * @param int $surahId Surah ID of the reviewed ayah
     * @param int $ayahId Ayah ID of the reviewed ayah
     * @param float $confidenceScore Confidence score from the review
     */
    public function __construct(
        User $student,
        MemorizationPlan $plan,
        int $surahId,
        int $ayahId,
        float $confidenceScore
    ) {
        $this->student = $student;
        $this->plan = $plan;
        $this->surahId = $surahId;
        $this->ayahId = $ayahId;
        $this->confidenceScore = $confidenceScore;
        $this->timestamp = now();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable The notifiable entity (teacher)
     * @return array Array of delivery channels
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Add email channel for significant milestones or low confidence scores
        if ($this->shouldSendEmail()) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable The notifiable entity
     * @return MailMessage Mail message instance
     */
    public function toMail($notifiable): MailMessage
    {
        $surahName = $this->getSurahName($this->surahId);
        $studentName = $this->student->name;
        $planTitle = $this->plan->title;
        
        $subject = $this->getEmailSubject();
        $greeting = "Assalamu Alaikum {$notifiable->name},";
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->getEmailContent())
            ->line("**Student:** {$studentName}")
            ->line("**Plan:** {$planTitle}")
            ->line("**Surah:** {$surahName}")
            ->line("**Ayah:** {$this->ayahId}")
            ->line("**Confidence Score:** {$this->confidenceScore}/5.0")
            ->action('View Student Progress', $this->getStudentProgressUrl())
            ->line('May Allah bless your teaching efforts.');
            
        return $message;
    }

    /**
     * Get the database representation of the notification.
     *
     * @param mixed $notifiable The notifiable entity
     * @return array Database notification data
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'memorization_progress',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'student_avatar' => $this->student->avatar_url,
            'plan_id' => $this->plan->id,
            'plan_title' => $this->plan->title,
            'surah_id' => $this->surahId,
            'surah_name' => $this->getSurahName($this->surahId),
            'ayah_id' => $this->ayahId,
            'confidence_score' => $this->confidenceScore,
            'progress_percentage' => $this->plan->getProgressPercentageAttribute(),
            'milestone' => $this->getMilestone(),
            'priority' => $this->getPriority(),
            'action_url' => $this->getStudentProgressUrl(),
            'timestamp' => $this->timestamp->toISOString(),
            'message' => $this->getNotificationMessage()
        ];
    }

    /**
     * Determine if email notification should be sent.
     *
     * @return bool True if email should be sent
     */
    private function shouldSendEmail(): bool
    {
        // Send email for low confidence scores (needs attention)
        if ($this->confidenceScore < 2.0) {
            return true;
        }
        
        // Send email for milestones (surah completion, plan completion)
        if ($this->getMilestone()) {
            return true;
        }
        
        // Send email for first review of the day (daily progress update)
        $cacheKey = "daily_email_sent_{$this->student->id}_{$this->plan->id}_" . now()->format('Y-m-d');
        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, true, now()->endOfDay());
            return true;
        }
        
        return false;
    }

    /**
     * Get email subject based on progress type.
     *
     * @return string Email subject
     */
    private function getEmailSubject(): string
    {
        $studentName = $this->student->name;
        
        if ($this->confidenceScore < 2.0) {
            return "📚 {$studentName} needs attention in memorization";
        }
        
        if ($this->getMilestone()) {
            return "🎉 {$studentName} achieved a memorization milestone!";
        }
        
        return "📖 Daily memorization update for {$studentName}";
    }

    /**
     * Get email content based on progress type.
     *
     * @return string Email content
     */
    private function getEmailContent(): string
    {
        $studentName = $this->student->name;
        $surahName = $this->getSurahName($this->surahId);
        
        if ($this->confidenceScore < 2.0) {
            return "Your student {$studentName} is struggling with {$surahName}, Ayah {$this->ayahId}. They may need additional support and guidance.";
        }
        
        if ($this->getMilestone()) {
            return "Excellent news! {$studentName} has achieved a significant milestone in their memorization journey with {$surahName}.";
        }
        
        return "Your student {$studentName} has completed a review session for {$surahName}, Ayah {$this->ayahId}.";
    }

    /**
     * Get notification message for database storage.
     *
     * @return string Notification message
     */
    private function getNotificationMessage(): string
    {
        $studentName = $this->student->name;
        $surahName = $this->getSurahName($this->surahId);
        
        if ($this->confidenceScore >= 4.5) {
            return "{$studentName} mastered {$surahName}, Ayah {$this->ayahId} with excellent confidence!";
        } elseif ($this->confidenceScore >= 3.0) {
            return "{$studentName} reviewed {$surahName}, Ayah {$this->ayahId} with good progress.";
        } else {
            return "{$studentName} needs support with {$surahName}, Ayah {$this->ayahId}.";
        }
    }

    /**
     * Determine if this review represents a milestone.
     *
     * @return string|null Milestone type or null
     */
    private function getMilestone(): ?string
    {
        // Check if this is surah completion (high confidence on last ayah)
        if ($this->confidenceScore >= 4.0) {
            $surahAyahCount = $this->getSurahAyahCount($this->surahId);
            if ($this->ayahId >= $surahAyahCount * 0.9) { // 90% through surah
                return 'surah_near_completion';
            }
        }
        
        // Check if plan is nearing completion
        $progressPercentage = $this->plan->getProgressPercentageAttribute();
        if ($progressPercentage >= 90) {
            return 'plan_near_completion';
        }
        
        // Check for mastery milestone (confidence >= 4.5)
        if ($this->confidenceScore >= 4.5) {
            return 'ayah_mastered';
        }
        
        return null;
    }

    /**
     * Get notification priority based on confidence score.
     *
     * @return string Priority level
     */
    private function getPriority(): string
    {
        if ($this->confidenceScore < 2.0) {
            return 'high'; // Needs attention
        } elseif ($this->confidenceScore >= 4.5) {
            return 'high'; // Excellent progress
        } else {
            return 'medium'; // Regular progress
        }
    }

    /**
     * Get surah name from cache or API.
     *
     * @param int $surahId Surah ID
     * @return string Surah name
     */
    private function getSurahName(int $surahId): string
    {
        $cacheKey = "surah_name_{$surahId}";
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($surahId) {
            // Fallback surah names (should be replaced with API call)
            $surahNames = [
                1 => 'Al-Fatihah', 2 => 'Al-Baqarah', 3 => 'Ali Imran', 4 => 'An-Nisa',
                5 => 'Al-Maidah', 6 => 'Al-Anam', 7 => 'Al-Araf', 8 => 'Al-Anfal',
                9 => 'At-Tawbah', 10 => 'Yunus', 11 => 'Hud', 12 => 'Yusuf',
                // ... (complete mapping would be needed)
            ];
            
            return $surahNames[$surahId] ?? "Surah {$surahId}";
        });
    }

    /**
     * Get approximate ayah count for a surah.
     *
     * @param int $surahId Surah ID
     * @return int Ayah count
     */
    private function getSurahAyahCount(int $surahId): int
    {
        // Approximate ayah counts (should be replaced with API call)
        $ayahCounts = [
            1 => 7, 2 => 286, 3 => 200, 4 => 176, 5 => 120, 6 => 165, 7 => 206,
            // ... (complete mapping would be needed)
        ];
        
        return $ayahCounts[$surahId] ?? 10;
    }

    /**
     * Get URL for viewing student progress.
     *
     * @return string Student progress URL
     */
    private function getStudentProgressUrl(): string
    {
        $baseUrl = config('app.frontend_url', config('app.url'));
        return "{$baseUrl}/teacher/students/{$this->student->id}/memorization";
    }

    /**
     * Get the notification's unique identifier for grouping.
     *
     * @return string Unique identifier
     */
    public function uniqueId(): string
    {
        return "memorization_{$this->student->id}_{$this->plan->id}_" . $this->timestamp->format('Y-m-d-H');
    }
}