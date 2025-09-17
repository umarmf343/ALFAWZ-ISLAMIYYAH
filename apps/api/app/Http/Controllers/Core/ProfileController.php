<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile with relationships.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'teachingClasses',
            'memberClasses',
            'assignments',
            'submissions.assignment',
        ]);

        return response()->json([
            'user' => $user->makeHidden(['password']),
        ]);
    }

    /**
     * Get teachers for the authenticated student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myTeachers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json([
                'message' => 'Only students can view their teachers',
                'teachers' => [],
            ]);
        }

        // Get teachers from classes the student is a member of
        $teachers = User::whereHas('teachingClasses.members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['teachingClasses' => function ($query) use ($user) {
            $query->whereHas('members', function ($subQuery) use ($user) {
                $subQuery->where('user_id', $user->id);
            });
        }])
        ->get()
        ->makeHidden(['password']);

        return response()->json([
            'teachers' => $teachers,
        ]);
    }

    /**
     * Get students for the authenticated teacher.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myStudents(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isTeacher()) {
            return response()->json([
                'message' => 'Only teachers can view their students',
                'students' => [],
            ]);
        }

        // Get students from classes taught by this teacher
        $students = User::whereHas('memberClasses', function ($query) use ($user) {
            $query->where('teacher_id', $user->id)
                  ->where('role_in_class', 'student');
        })
        ->with(['memberClasses' => function ($query) use ($user) {
            $query->where('teacher_id', $user->id);
        }])
        ->get()
        ->makeHidden(['password']);

        return response()->json([
            'students' => $students,
        ]);
    }

    /**
     * Update user profile information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:3'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->makeHidden(['password']),
        ]);
    }

    /**
     * Get user statistics and progress.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = [];

        if ($user->isStudent()) {
            $stats = [
                'classes_joined' => $user->memberClasses()->count(),
                'assignments_completed' => $user->submissions()->where('status', 'graded')->count(),
                'assignments_pending' => $user->submissions()->where('status', 'pending')->count(),
                'average_score' => $user->submissions()
                    ->where('status', 'graded')
                    ->whereNotNull('score')
                    ->avg('score') ?? 0,
                'total_hasanat' => $user->hasanat_earned ?? 0,
            ];
        } elseif ($user->isTeacher()) {
            $stats = [
                'classes_teaching' => $user->teachingClasses()->count(),
                'total_students' => $user->getStudents()->count(),
                'assignments_created' => $user->assignments()->count(),
                'submissions_to_grade' => \App\Models\Submission::whereHas('assignment', function ($query) use ($user) {
                    $query->where('teacher_id', $user->id);
                })->where('status', 'pending')->count(),
            ];
        }

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Get user's recent activity.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        $user = $request->user();
        $activities = [];

        if ($user->isStudent()) {
            // Recent submissions
            $recentSubmissions = $user->submissions()
                ->with(['assignment.class'])
                ->latest()
                ->limit(10)
                ->get();

            foreach ($recentSubmissions as $submission) {
                $activities[] = [
                    'type' => 'submission',
                    'title' => 'Submitted: ' . $submission->assignment->title,
                    'description' => 'Class: ' . $submission->assignment->class->title,
                    'date' => $submission->created_at,
                    'status' => $submission->status,
                ];
            }
        } elseif ($user->isTeacher()) {
            // Recent assignments created
            $recentAssignments = $user->assignments()
                ->with('class')
                ->latest()
                ->limit(5)
                ->get();

            foreach ($recentAssignments as $assignment) {
                $activities[] = [
                    'type' => 'assignment_created',
                    'title' => 'Created: ' . $assignment->title,
                    'description' => 'Class: ' . $assignment->class->title,
                    'date' => $assignment->created_at,
                    'status' => $assignment->status,
                ];
            }
        }

        // Sort by date descending
        usort($activities, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return response()->json([
            'activities' => array_slice($activities, 0, 10),
        ]);
    }
}