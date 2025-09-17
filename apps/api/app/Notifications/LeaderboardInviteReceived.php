<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\LeaderboardInvite;
use App\Models\User;

/**
 * Notification sent when a user receives a leaderboard invite.
 * Handles both database and mail notifications for community engagement.
 */
class LeaderboardInviteReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public LeaderboardInvite $invite;
    public User $sender;

    /**
     * Create a new notification instance.
     *
     * @param LeaderboardInvite $invite The leaderboard invite
     * @param User $sender The user who sent the invite
     */
    public function __construct(LeaderboardInvite $invite, User $sender)
    {
        $this->invite = $invite;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return array<int, string> Array of delivery channels
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
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
        $senderName = $this->sender->name;
        
        return (new MailMessage)
            ->subject("Leaderboard Invite from {$senderName} - {$appName}")
            ->greeting("Assalamu Alaikum {$notifiable->name}!")
            ->line("{$senderName} has invited you to join the Qur'an learning leaderboard.")
            ->when($this->invite->message, function ($mail) {
                return $mail->line('Personal message: "' . $this->invite->message . '"');
            })
            ->line('Join the community to track your progress, earn hasanat, and motivate each other in memorizing the Holy Qur\'an.')
            ->action('View Invite', url('/dashboard/student?tab=leaderboard'))
            ->line('May Allah bless your Qur\'anic journey!')
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
        return [
            'type' => 'leaderboard_invite',
            'invite_id' => $this->invite->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_avatar' => $this->sender->avatar_url ?? null,
            'message' => $this->invite->message,
            'created_at' => $this->invite->created_at->toISOString(),
            'action_url' => '/dashboard/student?tab=leaderboard',
            'title' => 'Leaderboard Invite Received',
            'body' => "{$this->sender->name} invited you to join the Qur'an learning leaderboard."
        ];
    }

    /**
     * Get the notification's database type.
     *
     * @return string The notification type for database queries
     */
    public function databaseType(): string
    {
        return 'leaderboard_invite';
    }

    /**
     * Determine if the notification should be sent.
     *
     * @param mixed $notifiable The user receiving the notification
     * @return bool Whether to send the notification
     */
    public function shouldSend(mixed $notifiable): bool
    {
        // Don't send if invite is no longer pending
        return $this->invite->status === 'pending' && 
               $this->invite->receiver_id === $notifiable->id;
    }
}