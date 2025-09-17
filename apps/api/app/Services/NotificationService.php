<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Services;

use App\Models\Assignment;
use App\Models\Notification;
use App\Models\User;
use App\Models\SrsQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send notification to teacher when student completes memorization review.
     * 
     * @param SrsQueue $review The completed review
     * @param User $student The student who completed the review
     * @return bool Success status
     */
    public function notifyTeacherOfCompletedReview(SrsQueue $review, User $student): bool
    {
        try {
            // Get the student's teachers
            $teachers = $this->getStudentTeachers($student);
            
            if ($teachers->isEmpty()) {
                Log::info('No teachers found for student', ['student_id' => $student->id]);
                return true;
            }

            foreach ($teachers as $teacher) {
                $this->sendReviewCompletionNotification($teacher, $student, $review);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to notify teachers of completed review', [
                'error' => $e->getMessage(),
                'student_id' => $student->id,
                'review_id' => $review->id
            ]);
            return false;
        }
    }

    /**
     * Send notification when student needs teacher review for audio submission.
     * 
     * @param SrsQueue $review The review needing teacher attention
     * @param User $student The student who submitted audio
     * @return bool Success status
     */
    public function notifyTeacherOfAudioReview(SrsQueue $review, User $student): bool
    {
        try {
            $teachers = $this->getStudentTeachers($student);
            
            if ($teachers->isEmpty()) {
                return true;
            }

            foreach ($teachers as $teacher) {
                $this->sendAudioReviewNotification($teacher, $student, $review);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to notify teachers of audio review', [
                'error' => $e->getMessage(),
                'student_id' => $student->id,
                'review_id' => $review->id
            ]);
            return false;
        }
    }

    /**
     * Send daily summary of student progress to teachers.
     * 
     * @param User $teacher The teacher to notify
     * @return bool Success status
     */
    public function sendDailyProgressSummary(User $teacher): bool
    {
        try {
            $students = $this->getTeacherStudents($teacher);
            $todayReviews = $this->getTodayReviewsForStudents($students->pluck('id'));
            
            if ($todayReviews->isEmpty()) {
                return true; // No activity to report
            }

            $this->sendDailySummaryEmail($teacher, $todayReviews);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send daily progress summary', [
                'error' => $e->getMessage(),
                'teacher_id' => $teacher->id
            ]);
            return false;
        }
    }

    /**
     * Get teachers for a specific student.
     * 
     * @param User $student The student
     * @return \Illuminate\Database\Eloquent\Collection Collection of teacher users
     */
    private function getStudentTeachers(User $student)
    {
        return User::whereHas('teachingClasses.members', function ($query) use ($student) {
            $query->where('user_id', $student->id);
        })->get();
    }

    /**
     * Get students for a specific teacher.
     * 
     * @param User $teacher The teacher
     * @return \Illuminate\Database\Eloquent\Collection Collection of student users
     */
    private function getTeacherStudents(User $teacher)
    {
        return User::whereHas('classMemberships.class', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })->get();
    }

    /**
     * Get today's reviews for specific students.
     * 
     * @param array $studentIds Array of student IDs
     * @return \Illuminate\Database\Eloquent\Collection Collection of reviews
     */
    private function getTodayReviewsForStudents(array $studentIds)
    {
        return SrsQueue::with(['user', 'ayah'])
            ->whereIn('user_id', $studentIds)
            ->whereDate('updated_at', today())
            ->where('status', 'completed')
            ->get();
    }

    /**
     * Send email notification for completed review.
     * 
     * @param User $teacher The teacher to notify
     * @param User $student The student who completed review
     * @param SrsQueue $review The completed review
     * @return void
     */
    private function sendReviewCompletionNotification(User $teacher, User $student, SrsQueue $review): void
    {
        $data = [
            'teacher_name' => $teacher->name,
            'student_name' => $student->name,
            'surah_name' => $review->ayah->surah_name ?? 'Unknown Surah',
            'ayah_number' => $review->ayah->ayah_number ?? 'Unknown',
            'quality' => $review->quality,
            'ease_factor' => $review->ease_factor,
            'completed_at' => $review->updated_at->format('M j, Y g:i A'),
            'dashboard_url' => config('app.frontend_url') . '/dashboard'
        ];

        Mail::send('emails.review-completed', $data, function ($message) use ($teacher, $student) {
            $message->to($teacher->email, $teacher->name)
                   ->subject("📚 {$student->name} completed a memorization review")
                   ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    /**
     * Send email notification for audio review needed.
     * 
     * @param User $teacher The teacher to notify
     * @param User $student The student who submitted audio
     * @param SrsQueue $review The review needing attention
     * @return void
     */
    private function sendAudioReviewNotification(User $teacher, User $student, SrsQueue $review): void
    {
        $data = [
            'teacher_name' => $teacher->name,
            'student_name' => $student->name,
            'surah_name' => $review->ayah->surah_name ?? 'Unknown Surah',
            'ayah_number' => $review->ayah->ayah_number ?? 'Unknown',
            'submitted_at' => $review->updated_at->format('M j, Y g:i A'),
            'review_url' => config('app.frontend_url') . '/dashboard?tab=reviews',
            'tajweed_score' => $review->tajweed_analysis['overall_score'] ?? 'N/A'
        ];

        Mail::send('emails.audio-review-needed', $data, function ($message) use ($teacher, $student) {
            $message->to($teacher->email, $teacher->name)
                   ->subject("🎤 {$student->name} submitted audio for review")
                   ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    /**
     * Send daily summary email to teacher.
     * 
     * @param User $teacher The teacher to notify
     * @param \Illuminate\Database\Eloquent\Collection $reviews Today's reviews
     * @return void
     */
    private function sendDailySummaryEmail(User $teacher, $reviews): void
    {
        $studentStats = $reviews->groupBy('user_id')->map(function ($studentReviews) {
            $student = $studentReviews->first()->user;
            return [
                'name' => $student->name,
                'reviews_count' => $studentReviews->count(),
                'avg_quality' => round($studentReviews->avg('quality'), 1),
                'ayahs_reviewed' => $studentReviews->count()
            ];
        });

        $data = [
            'teacher_name' => $teacher->name,
            'date' => today()->format('M j, Y'),
            'total_reviews' => $reviews->count(),
            'active_students' => $studentStats->count(),
            'student_stats' => $studentStats->values(),
            'dashboard_url' => config('app.frontend_url') . '/dashboard'
        ];

        Mail::send('emails.daily-progress-summary', $data, function ($message) use ($teacher) {
            $message->to($teacher->email, $teacher->name)
                   ->subject("📊 Daily Memorization Progress Summary - " . today()->format('M j, Y'))
                   ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    /**
     * Create in-app notification record.
     * 
     * @param User $user The user to notify
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return void
     */
    public function createInAppNotification(User $user, string $type, string $title, string $message, array $data = []): void
    {
        DB::table('notifications')->insert([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Mark notification as read.
     * 
     * @param int $notificationId The notification ID
     * @param int $userId The user ID
     * @return bool Success status
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return DB::table('notifications')
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['read_at' => now()]) > 0;
    }

    /**
     * Get unread notifications for user.
     * 
     * @param User $user The user
     * @param int $limit Maximum number of notifications
     * @return array Array of notifications
     */
    public function getUnreadNotifications(User $user, int $limit = 10): array
    {
        return DB::table('notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                $notification->data = json_decode($notification->data, true);
                return $notification;
            })
            ->toArray();
    }
}