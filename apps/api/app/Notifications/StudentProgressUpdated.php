<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Notifications;

use App\Models\User;
use App\Models\QuranProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to teachers when students make progress.
 * Helps teachers track student engagement and recitation activity.
 */
class StudentProgressUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $student,
        public QuranProgress $progress
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student Progress Update - ' . $this->student->name)
            ->greeting('Assalamu Alaikum!')
            ->line("Your student {$this->student->name} has made progress in their Qur'an recitation.")
            ->line("Surah: {$this->progress->surah_id}, Ayah: {$this->progress->ayah_number}")
            ->line("Total recitations: {$this->progress->recited_count}")
            ->line("Hasanat earned: {$this->progress->hasanat}")
            ->action('View Student Progress', url('/teacher/students/' . $this->student->id))
            ->line('May Allah reward both you and your student for this dedication to learning the Qur\'an.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'surah_id' => $this->progress->surah_id,
            'ayah_number' => $this->progress->ayah_number,
            'recited_count' => $this->progress->recited_count,
            'hasanat' => $this->progress->hasanat,
            'timestamp' => now()->toISOString(),
        ];
    }
}