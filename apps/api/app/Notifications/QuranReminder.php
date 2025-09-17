<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;
use Carbon\Carbon;

/**
 * Notification to remind users about daily Quran recitation.
 * Helps maintain learning streaks and encourages consistent practice.
 */
class QuranReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public string $reminderType;
    public array $reminderData;

    /**
     * Create a new notification instance.
     *
     * @param string $reminderType Type of reminder (daily, streak_risk, milestone)
     * @param array $reminderData Additional data for the reminder
     */
    public function __construct(string $reminderType, array $reminderData = [])
    {
        $this->reminderType = $reminderType;
        $this->reminderData = $reminderData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return array<int, string> Array of delivery channels
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['database'];
        
        // Add mail for important reminders if user preferences allow
        if (in_array($this->reminderType, ['streak_risk', 'milestone']) && 
            $this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return MailMessage The mail message
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $appName = config('app.name', 'AlFawz Qur\'an Institute');
        $userName = $notifiable->name;
        
        $mail = (new MailMessage)
            ->greeting("Assalamu Alaikum {$userName}!");
            
        switch ($this->reminderType) {
            case 'daily':
                $mail->subject("Daily Qur'an Reminder - {$appName}")
                     ->line('It\'s time for your daily Qur\'an recitation!')
                     ->line('Continue building your spiritual connection and earning hasanat.')
                     ->action('Start Reciting', url('/dashboard/student?tab=memorization'));
                break;
                
            case 'streak_risk':
                $currentStreak = $this->reminderData['current_streak'] ?? 0;
                $mail->subject("Don't Break Your {$currentStreak}-Day Streak! - {$appName}")
                     ->line("You have an amazing {$currentStreak}-day recitation streak!")
                     ->line('Don\'t let it end today. A few minutes of recitation will keep your momentum going.')
                     ->action('Continue Streak', url('/dashboard/student?tab=memorization'));
                break;
                
            case 'milestone':
                $milestone = $this->reminderData['milestone'] ?? 'achievement';
                $mail->subject("Congratulations on Your {$milestone}! - {$appName}")
                     ->line("Masha'Allah! You've reached an important milestone in your Qur'anic journey.")
                     ->line("Achievement: {$milestone}")
                     ->line('Keep up the excellent work and continue growing in your recitation.')
                     ->action('View Progress', url('/dashboard/student'));
                break;
        }
        
        return $mail->line('May Allah bless your efforts in learning His Holy Book.')
                   ->salutation('Barakallahu feeki,\nThe ' . $appName . ' Team');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return array<string, mixed> Notification data for database
     */
    public function toArray(mixed $notifiable): array
    {
        $baseData = [
            'type' => 'quran_reminder',
            'reminder_type' => $this->reminderType,
            'created_at' => now()->toISOString(),
        ];
        
        switch ($this->reminderType) {
            case 'daily':
                return array_merge($baseData, [
                    'title' => 'Daily Qur\'an Reminder',
                    'body' => 'Time for your daily recitation. Continue your spiritual journey!',
                    'action_url' => '/dashboard/student?tab=memorization',
                    'icon' => 'book-open',
                    'priority' => 'normal'
                ]);
                
            case 'streak_risk':
                $streak = $this->reminderData['current_streak'] ?? 0;
                return array_merge($baseData, [
                    'title' => 'Streak at Risk!',
                    'body' => "Don't break your {$streak}-day streak. Recite today to keep it going!",
                    'action_url' => '/dashboard/student?tab=memorization',
                    'icon' => 'flame',
                    'priority' => 'high',
                    'current_streak' => $streak
                ]);
                
            case 'milestone':
                $milestone = $this->reminderData['milestone'] ?? 'achievement';
                return array_merge($baseData, [
                    'title' => 'Milestone Achieved!',
                    'body' => "Masha'Allah! You've reached: {$milestone}",
                    'action_url' => '/dashboard/student',
                    'icon' => 'trophy',
                    'priority' => 'high',
                    'milestone' => $milestone,
                    'hasanat_earned' => $this->reminderData['hasanat_earned'] ?? 0
                ]);
                
            default:
                return array_merge($baseData, [
                    'title' => 'Qur\'an Reminder',
                    'body' => 'Continue your Qur\'anic learning journey.',
                    'action_url' => '/dashboard/student',
                    'icon' => 'book',
                    'priority' => 'normal'
                ]);
        }
    }

    /**
     * Get the notification's database type.
     *
     * @return string The notification type for database queries
     */
    public function databaseType(): string
    {
        return 'quran_reminder';
    }

    /**
     * Determine if email should be sent based on user preferences.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return bool Whether to send email
     */
    private function shouldSendEmail(mixed $notifiable): bool
    {
        // Check user notification preferences
        $preferences = $notifiable->notification_preferences ?? [];
        
        // Default to true for important notifications if no preference set
        return $preferences['email_reminders'] ?? true;
    }

    /**
     * Determine if the notification should be sent.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return bool Whether to send the notification
     */
    public function shouldSend(mixed $notifiable): bool
    {
        // Don't send daily reminders too frequently
        if ($this->reminderType === 'daily') {
            $lastReminder = $notifiable->notifications()
                ->where('type', self::class)
                ->where('data->reminder_type', 'daily')
                ->where('created_at', '>=', now()->subHours(20))
                ->first();
                
            return !$lastReminder;
        }
        
        return true;
    }
}