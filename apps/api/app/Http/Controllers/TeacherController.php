<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Notification;

class TeacherController extends Controller
{
    /**
     * Get comprehensive dashboard data for the authenticated teacher.
     * Returns analytics, recent activities, and summary statistics.
     */
    public function getDashboardData(): JsonResponse
    {
        $teacher = Auth::user();
        
        // Get teacher analytics
        $analytics = DB::table('teacher_analytics')
            ->where('teacher_id', $teacher->id)
            ->first();

        // Get recent submissions for review
        $recentSubmissions = Submission::with(['assignment', 'student'])
            ->whereHas('assignment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get active assignments count
        $activeAssignments = Assignment::where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'analytics' => $analytics ?: [
                'total_students' => 0,
                'completion_rate' => 0,
                'hotspot_interactions' => 0,
                'game_sessions' => 0,
                'high_scores' => 0,
                'active_assignments' => $activeAssignments,
                'pending_submissions' => $recentSubmissions->count(),
            ],
            'recent_submissions' => $recentSubmissions,
            'active_assignments_count' => $activeAssignments,
        ]);
    }

    /**
     * Get notifications for the authenticated teacher.
     * Supports pagination and filtering by read/unread status.
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $teacher = Auth::user();
        $perPage = $request->get('per_page', 10);
        $filter = $request->get('filter', 'all'); // all, read, unread

        $query = Notification::where('user_id', $teacher->id)
            ->orderBy('created_at', 'desc');

        if ($filter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     * Updates the read_at timestamp for the specified notification.
     */
    public function markNotificationAsRead(Request $request, $notificationId): JsonResponse
    {
        $teacher = Auth::user();
        
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $teacher->id)
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Get student progress data for the teacher's classes.
     * Returns detailed progress metrics for each student.
     */
    public function getStudentProgress(): JsonResponse
    {
        $teacher = Auth::user();

        // Get students from teacher's classes
        $students = DB::table('class_members')
            ->join('classes', 'class_members.class_id', '=', 'classes.id')
            ->join('users', 'class_members.user_id', '=', 'users.id')
            ->where('classes.teacher_id', $teacher->id)
            ->where('users.role', 'student')
            ->select('users.*')
            ->distinct()
            ->get();

        $progressData = [];
        
        foreach ($students as $student) {
            // Get submission statistics
            $totalAssignments = Assignment::where('teacher_id', $teacher->id)
                ->whereHas('classes.members', function ($query) use ($student) {
                    $query->where('user_id', $student->id);
                })
                ->count();

            $completedSubmissions = Submission::where('student_id', $student->id)
                ->whereHas('assignment', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->where('status', 'completed')
                ->count();

            $averageScore = Submission::where('student_id', $student->id)
                ->whereHas('assignment', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })
                ->whereNotNull('score')
                ->avg('score') ?: 0;

            $progressData[] = [
                'student' => $student,
                'total_assignments' => $totalAssignments,
                'completed_submissions' => $completedSubmissions,
                'completion_rate' => $totalAssignments > 0 ? round(($completedSubmissions / $totalAssignments) * 100, 2) : 0,
                'average_score' => round($averageScore, 2),
                'last_activity' => Submission::where('student_id', $student->id)
                    ->whereHas('assignment', function ($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    })
                    ->latest()
                    ->value('created_at'),
            ];
        }

        return response()->json($progressData);
    }

    /**
     * Get game analytics data for the teacher's students.
     * Returns game session statistics and performance metrics.
     */
    public function getGameAnalytics(): JsonResponse
    {
        $teacher = Auth::user();

        // Mock game analytics data (replace with actual game system integration)
        $gameAnalytics = [
            'total_sessions' => rand(150, 300),
            'average_session_duration' => rand(8, 15) . ' minutes',
            'most_popular_game' => 'Quran Memory Challenge',
            'top_performers' => [
                ['name' => 'Ahmad Ali', 'score' => 2450, 'level' => 'Advanced'],
                ['name' => 'Fatima Hassan', 'score' => 2380, 'level' => 'Advanced'],
                ['name' => 'Omar Ibrahim', 'score' => 2200, 'level' => 'Intermediate'],
            ],
            'weekly_activity' => [
                ['day' => 'Monday', 'sessions' => rand(20, 40)],
                ['day' => 'Tuesday', 'sessions' => rand(25, 45)],
                ['day' => 'Wednesday', 'sessions' => rand(30, 50)],
                ['day' => 'Thursday', 'sessions' => rand(28, 48)],
                ['day' => 'Friday', 'sessions' => rand(35, 55)],
                ['day' => 'Saturday', 'sessions' => rand(40, 60)],
                ['day' => 'Sunday', 'sessions' => rand(32, 52)],
            ],
            'game_types' => [
                ['name' => 'Memory Games', 'sessions' => rand(80, 120), 'avg_score' => rand(75, 95)],
                ['name' => 'Tajweed Practice', 'sessions' => rand(60, 100), 'avg_score' => rand(70, 90)],
                ['name' => 'Quran Quiz', 'sessions' => rand(70, 110), 'avg_score' => rand(65, 85)],
            ],
        ];

        return response()->json($gameAnalytics);
    }

    /**
     * Update teacher analytics data.
     * Recalculates and updates analytics metrics for the teacher.
     */
    public function updateAnalytics(): JsonResponse
    {
        $teacher = Auth::user();

        // Calculate updated analytics
        $totalStudents = DB::table('class_members')
            ->join('classes', 'class_members.class_id', '=', 'classes.id')
            ->where('classes.teacher_id', $teacher->id)
            ->distinct('class_members.user_id')
            ->count();

        $totalAssignments = Assignment::where('teacher_id', $teacher->id)->count();
        $completedSubmissions = Submission::whereHas('assignment', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })->where('status', 'completed')->count();

        $completionRate = $totalAssignments > 0 ? ($completedSubmissions / $totalAssignments) * 100 : 0;

        // Update or create analytics record
        DB::table('teacher_analytics')->updateOrInsert(
            ['teacher_id' => $teacher->id],
            [
                'total_students' => $totalStudents,
                'completion_rate' => round($completionRate, 2),
                'hotspot_interactions' => rand(100, 300), // Mock data
                'game_sessions' => rand(80, 200), // Mock data
                'high_scores' => rand(20, 60), // Mock data
                'active_assignments' => Assignment::where('teacher_id', $teacher->id)->where('status', 'active')->count(),
                'pending_submissions' => Submission::whereHas('assignment', function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                })->where('status', 'pending')->count(),
                'last_updated' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['message' => 'Analytics updated successfully']);
    }

    /**
     * Grade a submission that belongs to the authenticated teacher.
     * Updates the submission score, rubric data and teacher feedback notes.
     */
    public function gradeSubmission(Request $request, Submission $submission): JsonResponse
    {
        $teacher = Auth::user();

        if (!$submission->assignment || $submission->assignment->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'rubric' => ['nullable', 'array'],
            'rubric.tajweed' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rubric.fluency' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rubric.memorization' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rubric.pronunciation' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $submission->update([
            'status' => Submission::STATUS_GRADED,
            'score' => $validated['score'],
            'rubric_json' => $validated['rubric'] ?? $submission->rubric_json,
            'teacher_notes' => $validated['feedback'] ?? $submission->teacher_notes,
            'reviewed_at' => now(),
        ]);

        $submission->refresh(['assignment', 'student']);

        return response()->json([
            'message' => 'Submission graded successfully',
            'submission' => $submission,
        ]);
    }
}