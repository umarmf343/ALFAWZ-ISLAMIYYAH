<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'AlFawz Qur\'an Institute');
        $frontendBase = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $verificationUrl = $frontendBase ? $frontendBase . '/verify-email' : null;

        $mail = (new MailMessage())
            ->subject("Verify your email for {$appName}")
            ->greeting("As-salaamu alaykum {$notifiable->name}!")
            ->line("Thank you for joining {$appName}.")
            ->line('To complete your registration, please verify your email address from within your account.');

        if ($verificationUrl) {
            $mail->action('Verify Email', $verificationUrl);
        }

        return $mail->line('You can also sign in to the app and choose "Verify Email" from your profile.');
    }
}
