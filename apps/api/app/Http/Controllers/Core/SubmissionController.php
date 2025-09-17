<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    /**
     * Display a listing of submissions.
     *
     * @param Request $request HTTP request with optional filters
     * @return JsonResponse JSON response with submissions data
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Submission::with(['assignment', 'student', 'feedback']);

        // Filter by assignment if provided
        if ($request->has('assignment_id')) {
            $assignmentId = $request->get('assignment_id');
            $assignment = Assignment::findOrFail($assignmentId);
            $this->authorize('view', $assignment);
            $query->where('assignment_id', $assignmentId);
        }

        // Role-based filtering
        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        } elseif ($user->hasRole('teacher')) {
            // Teachers can see submissions for their assignments
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }
        // Admins can see all submissions

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by completion status
        if ($request->has('completed')) {
            $isCompleted = filter_var($request->get('completed'), FILTER_VALIDATE_BOOLEAN);
            if ($isCompleted) {
                $query->whereIn('status', [Submission::STATUS_COMPLETED, Submission::STATUS_REVIEWED]);
            } else {
                $query->whereNotIn('status', [Submission::STATUS_COMPLETED, Submission::STATUS_REVIEWED]);
            }
        }

        $submissions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total()
            ]
        ]);
    }

    /**
     * Store a newly created submission.
     *
     * @param Request $request HTTP request with submission data
     * @return JsonResponse JSON response with created submission
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:51200', // 50MB max
            'text_content' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:1000',
            'hotspot_interactions' => 'nullable|array',
            'hotspot_interactions.*.hotspot_id' => 'required|exists:hotspots,id',
            'hotspot_interactions.*.interaction_type' => ['required', Rule::in(['view', 'click', 'audio_play'])],
            'hotspot_interactions.*.duration_seconds' => 'nullable|integer|min:0'
        ]);

        $assignment = Assignment::findOrFail($validated['assignment_id']);
        $this->authorize('submit', $assignment);

        $user = Auth::user();

        // Check if user already has a submission for this assignment
        $existingSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existingSubmission && $existingSubmission->status === Submission::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'You have already completed this assignment'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $submissionData = [
                'assignment_id' => $assignment->id,
                'student_id' => $user->id,
                'text_content' => $validated['text_content'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => Submission::STATUS_DRAFT,
                'started_at' => now(),
                'hotspot_interactions' => $validated['hotspot_interactions'] ?? []
            ];

            // Handle audio upload
            if ($request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store('submissions/audio', 'public');
                $submissionData['audio_url'] = Storage::url($audioPath);
                $submissionData['audio_duration'] = $this->getAudioDuration($audioPath);
            }

            $submission = $existingSubmission 
                ? tap($existingSubmission)->update($submissionData)
                : Submission::create($submissionData);

            // Record hotspot interactions
            if (!empty($validated['hotspot_interactions'])) {
                foreach ($validated['hotspot_interactions'] as $interaction) {
                    $hotspot = \App\Models\Hotspot::find($interaction['hotspot_id']);
                    if ($hotspot && $hotspot->assignment_id === $assignment->id) {
                        $hotspot->recordInteraction(
                            $user->id,
                            $interaction['interaction_type'],
                            $interaction['duration_seconds'] ?? null
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $existingSubmission ? 'Submission updated successfully' : 'Submission created successfully',
                'data' => $submission->fresh()->toFrontendArray()
            ], $existingSubmission ? 200 : 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified submission.
     *
     * @param Submission $submission The submission to display
     * @return JsonResponse JSON response with submission data
     */
    public function show(Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);

        $submission->load(['assignment', 'student', 'feedback.teacher']);

        return response()->json([
            'success' => true,
            'data' => $submission->toFrontendArray()
        ]);
    }

    /**
     * Update the specified submission.
     *
     * @param Request $request HTTP request with updated submission data
     * @param Submission $submission The submission to update
     * @return JsonResponse JSON response with updated submission
     */
    public function update(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        // Prevent updates to completed/reviewed submissions
        if (in_array($submission->status, [Submission::STATUS_COMPLETED, Submission::STATUS_REVIEWED])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a completed or reviewed submission'
            ], 422);
        }

        $validated = $request->validate([
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:51200',
            'text_content' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:1000',
            'status' => ['sometimes', Rule::in([Submission::STATUS_DRAFT, Submission::STATUS_IN_PROGRESS, Submission::STATUS_COMPLETED])],
            'hotspot_interactions' => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            $updateData = [];

            if (isset($validated['text_content'])) {
                $updateData['text_content'] = $validated['text_content'];
            }

            if (isset($validated['notes'])) {
                $updateData['notes'] = $validated['notes'];
            }

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
                
                if ($validated['status'] === Submission::STATUS_COMPLETED) {
                    $updateData['completed_at'] = now();
                    $updateData['completion_percentage'] = $submission->calculateCompletionPercentage();
                    
                    // Award hasanat for completion
                    $submission->awardHasanat();
                }
            }

            if (isset($validated['hotspot_interactions'])) {
                $updateData['hotspot_interactions'] = $validated['hotspot_interactions'];
            }

            // Handle audio upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file
                if ($submission->audio_url && Storage::exists($submission->audio_url)) {
                    Storage::delete($submission->audio_url);
                }

                $audioPath = $request->file('audio_file')->store('submissions/audio', 'public');
                $updateData['audio_url'] = Storage::url($audioPath);
                $updateData['audio_duration'] = $this->getAudioDuration($audioPath);
            }

            $submission->update($updateData);

            // Process AI analysis if audio was uploaded and submission is completed
            if (isset($updateData['audio_url']) && $submission->status === Submission::STATUS_COMPLETED) {
                $submission->processAIAnalysis();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Submission updated successfully',
                'data' => $submission->fresh()->toFrontendArray()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a draft submission for review.
     *
     * @param Submission $submission The submission to submit
     * @return JsonResponse JSON response confirming submission
     */
    public function submit(Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        if ($submission->status !== Submission::STATUS_DRAFT && $submission->status !== Submission::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or in-progress submissions can be submitted'
            ], 422);
        }

        $submission->update([
            'status' => Submission::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_percentage' => $submission->calculateCompletionPercentage()
        ]);

        // Award hasanat for completion
        $submission->awardHasanat();

        // Process AI analysis if audio exists
        if ($submission->hasAudio()) {
            $submission->processAIAnalysis();
        }

        // Create notification for teacher
        $submission->assignment->createNotification(
            $submission->assignment->teacher_id,
            'submission_completed',
            "New submission from {$submission->student->name} for assignment: {$submission->assignment->title}",
            ['submission_id' => $submission->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Submission submitted successfully',
            'data' => $submission->fresh()->toFrontendArray()
        ]);
    }

    /**
     * Get submission analytics for teachers.
     *
     * @param Request $request HTTP request with optional filters
     * @return JsonResponse JSON response with analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasRole('teacher') && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $query = Submission::query();

        // Filter by teacher's assignments if not admin
        if ($user->hasRole('teacher')) {
            $query->whereHas('assignment', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        // Filter by assignment if provided
        if ($request->has('assignment_id')) {
            $query->where('assignment_id', $request->get('assignment_id'));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->get('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->get('end_date'));
        }

        $analytics = [
            'total_submissions' => $query->count(),
            'completed_submissions' => $query->where('status', Submission::STATUS_COMPLETED)->count(),
            'reviewed_submissions' => $query->where('status', Submission::STATUS_REVIEWED)->count(),
            'average_completion_percentage' => $query->where('completion_percentage', '>', 0)->avg('completion_percentage'),
            'submissions_by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status'),
            'submissions_by_day' => $query->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->limit(30)
                ->pluck('count', 'date')
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get audio duration in seconds.
     *
     * @param string $audioPath Path to audio file
     * @return int|null Duration in seconds or null if unable to determine
     */
    private function getAudioDuration(string $audioPath): ?int
    {
        try {
            $fullPath = Storage::path($audioPath);
            if (function_exists('getid3_analyze')) {
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($fullPath);
                return isset($fileInfo['playtime_seconds']) ? (int) $fileInfo['playtime_seconds'] : null;
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}