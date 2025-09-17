<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Submission;
use App\Models\Hotspot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /**
     * Display a listing of assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Assignment::with(['class', 'teacher', 'hotspots']);
        
        // Filter by user role
        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('student')) {
            // Get assignments from classes the student is enrolled in
            $classIds = $user->studentClasses()->pluck('classes.id');
            $query->whereIn('class_id', $classIds);
        }
        
        // Apply filters
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort by due date or created date
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $assignments = $query->paginate($request->get('per_page', 15));
        
        // Add submission status for students
        if ($user->hasRole('student')) {
            $assignments->getCollection()->transform(function ($assignment) use ($user) {
                $submission = $assignment->submissions()->where('student_id', $user->id)->first();
                $assignment->submission_status = $submission ? $submission->status : 'not_started';
                $assignment->submission_id = $submission?->id;
                $assignment->completion_percentage = $submission?->completion_percentage ?? 0;
                return $assignment;
            });
        }
        
        return response()->json([
            'assignments' => $assignments,
            'meta' => [
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage()
            ]
        ]);
    }

    /**
     * Store a newly created assignment.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only teachers can create assignments
        if (!$user->hasRole('teacher')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['reading', 'memorization', 'listening', 'writing'])],
            'quran_surah' => 'required|integer|min:1|max:114',
            'quran_ayah_start' => 'required|integer|min:1',
            'quran_ayah_end' => 'required|integer|min:1',
            'difficulty_level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'expected_hasanat' => 'required|integer|min:1',
            'requires_audio' => 'boolean',
            'max_attempts' => 'nullable|integer|min:1',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date|after:now',
            'instructions' => 'nullable|string',
            'media_url' => 'nullable|url',
            'media_type' => ['nullable', Rule::in(['audio', 'video', 'image'])],
            'settings' => 'nullable|array'
        ]);
        
        // Verify teacher owns the class
        $class = ClassModel::findOrFail($validated['class_id']);
        if ($class->teacher_id !== $user->id) {
            return response()->json(['error' => 'You can only create assignments for your own classes'], 403);
        }
        
        $validated['teacher_id'] = $user->id;
        $validated['status'] = 'draft';
        
        $assignment = Assignment::create($validated);
        
        // Load relationships for response
        $assignment->load(['class', 'teacher', 'hotspots']);
        
        return response()->json([
            'message' => 'Assignment created successfully',
            'assignment' => $assignment
        ], 201);
    }

    /**
     * Display the specified assignment.
     */
    public function show(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Check access permissions
        if ($user->hasRole('teacher') && $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($user->hasRole('student')) {
            $isEnrolled = $user->studentClasses()->where('classes.id', $assignment->class_id)->exists();
            if (!$isEnrolled) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }
        
        $assignment->load([
            'class',
            'teacher',
            'hotspots' => function($query) {
                $query->orderBy('order_index');
            }
        ]);
        
        // Add submission data for students
        if ($user->hasRole('student')) {
            $submission = $assignment->submissions()->where('student_id', $user->id)->first();
            $assignment->submission = $submission;
            $assignment->submission_status = $submission ? $submission->status : 'not_started';
            $assignment->completion_percentage = $submission?->completion_percentage ?? 0;
            
            // Start submission if not exists
            if (!$submission && $assignment->isActive()) {
                $submission = Submission::create([
                    'assignment_id' => $assignment->id,
                    'student_id' => $user->id,
                    'status' => 'in_progress'
                ]);
                $submission->start();
                $assignment->submission = $submission;
            }
        }
        
        // Add statistics for teachers
        if ($user->hasRole('teacher')) {
            $assignment->statistics = [
                'total_students' => $assignment->class->members()->count(),
                'submissions_count' => $assignment->submissions()->count(),
                'completed_count' => $assignment->submissions()->completed()->count(),
                'average_score' => $assignment->getAverageScore(),
                'completion_rate' => $assignment->getCompletionRate()
            ];
        }
        
        return response()->json(['assignment' => $assignment]);
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Only assignment owner can update
        if (!$user->hasRole('teacher') || $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Prevent updates to published assignments with submissions
        if ($assignment->status === 'published' && $assignment->submissions()->exists()) {
            return response()->json([
                'error' => 'Cannot update published assignment with existing submissions'
            ], 422);
        }
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => ['sometimes', Rule::in(['reading', 'memorization', 'listening', 'writing'])],
            'quran_surah' => 'sometimes|integer|min:1|max:114',
            'quran_ayah_start' => 'sometimes|integer|min:1',
            'quran_ayah_end' => 'sometimes|integer|min:1',
            'difficulty_level' => ['sometimes', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'expected_hasanat' => 'sometimes|integer|min:1',
            'requires_audio' => 'boolean',
            'max_attempts' => 'nullable|integer|min:1',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
            'instructions' => 'nullable|string',
            'media_url' => 'nullable|url',
            'media_type' => ['nullable', Rule::in(['audio', 'video', 'image'])],
            'settings' => 'nullable|array'
        ]);
        
        $assignment->update($validated);
        
        $assignment->load(['class', 'teacher', 'hotspots']);
        
        return response()->json([
            'message' => 'Assignment updated successfully',
            'assignment' => $assignment
        ]);
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Only assignment owner can delete
        if (!$user->hasRole('teacher') || $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Prevent deletion of assignments with submissions
        if ($assignment->submissions()->exists()) {
            return response()->json([
                'error' => 'Cannot delete assignment with existing submissions'
            ], 422);
        }
        
        DB::transaction(function () use ($assignment) {
            // Delete associated hotspots
            $assignment->hotspots()->delete();
            
            // Delete the assignment
            $assignment->delete();
        });
        
        return response()->json(['message' => 'Assignment deleted successfully']);
    }

    /**
     * Publish an assignment (make it active for students).
     */
    public function publish(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Only assignment owner can publish
        if (!$user->hasRole('teacher') || $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($assignment->status === 'published') {
            return response()->json(['error' => 'Assignment is already published'], 422);
        }
        
        $assignment->update([
            'status' => 'published',
            'published_at' => now()
        ]);
        
        // Create notifications for all students in the class
        $assignment->createNotificationsForStudents();
        
        return response()->json([
            'message' => 'Assignment published successfully',
            'assignment' => $assignment
        ]);
    }

    /**
     * Get assignment submissions (for teachers).
     */
    public function submissions(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Only assignment owner can view submissions
        if (!$user->hasRole('teacher') || $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $submissions = $assignment->submissions()
            ->with(['student', 'feedback'])
            ->orderBy('submitted_at', 'desc')
            ->get();
        
        $submissions->transform(function ($submission) {
            return [
                'id' => $submission->id,
                'student' => [
                    'id' => $submission->student->id,
                    'name' => $submission->student->name,
                    'email' => $submission->student->email
                ],
                'status' => $submission->status,
                'completion_percentage' => $submission->completion_percentage,
                'hasanat_earned' => $submission->hasanat_earned,
                'overall_score' => $submission->overall_score,
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'time_spent' => $submission->getFormattedTimeSpent(),
                'needs_review' => $submission->needsReview(),
                'feedback_count' => $submission->feedback->count(),
                'has_audio' => $submission->hasAudio()
            ];
        });
        
        return response()->json(['submissions' => $submissions]);
    }

    /**
     * Get assignment analytics (for teachers).
     */
    public function analytics(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Only assignment owner can view analytics
        if (!$user->hasRole('teacher') || $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $totalStudents = $assignment->class->members()->count();
        $submissions = $assignment->submissions();
        
        $analytics = [
            'overview' => [
                'total_students' => $totalStudents,
                'submissions_count' => $submissions->count(),
                'completion_rate' => $assignment->getCompletionRate(),
                'average_score' => $assignment->getAverageScore(),
                'total_hasanat_awarded' => $submissions->sum('hasanat_earned')
            ],
            'status_breakdown' => [
                'not_started' => $totalStudents - $submissions->count(),
                'in_progress' => $submissions->where('status', 'in_progress')->count(),
                'submitted' => $submissions->where('status', 'submitted')->count(),
                'reviewed' => $submissions->where('status', 'reviewed')->count()
            ],
            'score_distribution' => [
                'excellent' => $submissions->where('overall_score', '>=', 90)->count(),
                'good' => $submissions->whereBetween('overall_score', [70, 89])->count(),
                'average' => $submissions->whereBetween('overall_score', [50, 69])->count(),
                'needs_improvement' => $submissions->where('overall_score', '<', 50)->count()
            ],
            'recent_submissions' => $submissions->with('student')
                ->orderBy('submitted_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($submission) {
                    return [
                        'student_name' => $submission->student->name,
                        'submitted_at' => $submission->submitted_at?->toISOString(),
                        'score' => $submission->overall_score,
                        'status' => $submission->status
                    ];
                })
        ];
        
        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Duplicate an assignment.
     */
    public function duplicate(Assignment $assignment): JsonResponse
    {
        $user = Auth::user();
        
        // Only assignment owner can duplicate
        if (!$user->hasRole('teacher') || $assignment->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        DB::beginTransaction();
        
        try {
            // Create new assignment
            $newAssignment = $assignment->replicate();
            $newAssignment->title = $assignment->title . ' (Copy)';
            $newAssignment->status = 'draft';
            $newAssignment->published_at = null;
            $newAssignment->save();
            
            // Duplicate hotspots
            foreach ($assignment->hotspots as $hotspot) {
                $newHotspot = $hotspot->replicate();
                $newHotspot->assignment_id = $newAssignment->id;
                $newHotspot->save();
            }
            
            DB::commit();
            
            $newAssignment->load(['class', 'teacher', 'hotspots']);
            
            return response()->json([
                'message' => 'Assignment duplicated successfully',
                'assignment' => $newAssignment
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to duplicate assignment'], 500);
        }
    }
}