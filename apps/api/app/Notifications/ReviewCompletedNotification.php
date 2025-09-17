<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\SrsQueue;

class ReviewCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $student;
    protected $srsReview;
    protected $surahName;
    protected $ayahNumber;
    
    /**
     * Create a new notification instance for completed memorization review.
     *
     * @param User $student The student who completed the review
     * @param SrsQueue $srsReview The SRS review record
     * @param string $surahName Name of the surah reviewed
     * @param int $ayahNumber Ayah number reviewed
     */
    public function __construct(User $student, SrsQueue $srsReview, string $surahName, int $ayahNumber)
    {
        $this->student = $student;
        $this->srsReview = $srsReview;
        $this->surahName = $surahName;
        $this->ayahNumber = $ayahNumber;
    }
    
    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return array Array of delivery channels
     */
    public function via($notifiable): array
    {
        $channels = ['database']; // Always send in-app notification
        
        // Add email channel if user has email notifications enabled
        if ($notifiable->email_notifications && $notifiable->review_completed_notifications) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }
    
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return MailMessage Email message instance
     */
    public function toMail($notifiable): MailMessage
    {
        $dashboardUrl = config('app.frontend_url') . '/teacher/dashboard';
        
        return (new MailMessage)
            ->subject('Student Review Completed - ' . $this->student->name)
            ->view('emails.review-completed', [
                'teacher_name' => $notifiable->name,
                'student_name' => $this->student->name,
                'surah_name' => $this->surahName,
                'ayah_number' => $this->ayahNumber,
                'quality' => $this->srsReview->quality,
                'ease_factor' => $this->srsReview->ease_factor,
                'completed_at' => $this->srsReview->updated_at->format('M j, Y g:i A'),
                'dashboard_url' => $dashboardUrl
            ]);
    }
    
    /**
     * Get the database representation of the notification.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return array Notification data for database storage
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Review Completed',
            'message' => "{$this->student->name} completed a memorization review for {$this->surahName}, Ayah {$this->ayahNumber}",
            'icon' => '📚',
            'priority' => 'medium',
            'action_url' => '/teacher/dashboard',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'surah_name' => $this->surahName,
            'ayah_number' => $this->ayahNumber,
            'quality' => $this->srsReview->quality,
            'ease_factor' => $this->srsReview->ease_factor,
            'review_id' => $this->srsReview->id,
            'completed_at' => $this->srsReview->updated_at->toISOString()
        ];
    }
    
    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return array Notification data array
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
    
    /**
     * Determine if the notification should be sent.
     *
     * @param mixed $notifiable The user receiving the notification
     * @param string $channel The delivery channel
     * @return bool True if notification should be sent
     */
    public function shouldSend($notifiable, $channel): bool
    {
        // Don't send if user is the student themselves
        if ($notifiable->id === $this->student->id) {
            return false;
        }
        
        // For email channel, check if user has email notifications enabled
        if ($channel === 'mail') {
            return $notifiable->email_notifications && $notifiable->review_completed_notifications;
        }
        
        return true;
    }
}