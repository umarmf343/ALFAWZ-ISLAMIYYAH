<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class DailyProgressSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $date;
    protected $totalReviews;
    protected $activeStudents;
    protected $avgQuality;
    protected $studentActivities;
    
    /**
     * Create a new daily progress summary notification.
     *
     * @param string $date Date for the summary (Y-m-d format)
     * @param int $totalReviews Total number of reviews completed
     * @param int $activeStudents Number of active students
     * @param float $avgQuality Average quality score
     * @param array $studentActivities Array of student activity data
     */
    public function __construct(
        string $date,
        int $totalReviews,
        int $activeStudents,
        float $avgQuality,
        array $studentActivities
    ) {
        $this->date = $date;
        $this->totalReviews = $totalReviews;
        $this->activeStudents = $activeStudents;
        $this->avgQuality = $avgQuality;
        $this->studentActivities = $studentActivities;
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
        
        // Add email channel if user has daily summary enabled
        if ($notifiable->email_notifications && $notifiable->daily_summary) {
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
        $formattedDate = Carbon::parse($this->date)->format('F j, Y');
        
        return (new MailMessage)
            ->subject('Daily Progress Summary - ' . $formattedDate)
            ->view('emails.daily-progress', [
                'teacher_name' => $notifiable->name,
                'date' => $formattedDate,
                'total_reviews' => $this->totalReviews,
                'active_students' => $this->activeStudents,
                'avg_quality' => $this->avgQuality,
                'student_activities' => $this->studentActivities,
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
        $formattedDate = Carbon::parse($this->date)->format('M j, Y');
        
        $message = $this->totalReviews > 0 
            ? "Daily summary for {$formattedDate}: {$this->totalReviews} reviews by {$this->activeStudents} students"
            : "Daily summary for {$formattedDate}: No memorization activities recorded";
        
        return [
            'title' => 'Daily Progress Summary',
            'message' => $message,
            'icon' => '📊',
            'priority' => 'low',
            'action_url' => '/teacher/dashboard',
            'date' => $this->date,
            'total_reviews' => $this->totalReviews,
            'active_students' => $this->activeStudents,
            'avg_quality' => $this->avgQuality,
            'student_activities' => $this->studentActivities,
            'summary_type' => 'daily'
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
        // Only send to teachers
        if (!$notifiable->isTeacher()) {
            return false;
        }
        
        // For email channel, check if user has daily summary enabled
        if ($channel === 'mail') {
            return $notifiable->email_notifications && $notifiable->daily_summary;
        }
        
        return true;
    }
    
    /**
     * Get the notification's tags for queue management.
     *
     * @return array Tags for queue processing
     */
    public function tags(): array
    {
        return ['daily-summary', 'teacher-notifications', $this->date];
    }
    
    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array Retry delays in seconds
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes
    }
    
    /**
     * Determine the number of times the job may be attempted.
     *
     * @return int Maximum number of attempts
     */
    public function tries(): int
    {
        return 3;
    }
}