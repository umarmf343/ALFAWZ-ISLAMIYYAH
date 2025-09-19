<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Submission;
use App\Services\WhisperService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    private WhisperService $whisperService;

    public function __construct(WhisperService $whisperService)
    {
        $this->whisperService = $whisperService;
    }
    /**
     * Display a listing of feedback.
     *
     * @param Request $request HTTP request with optional filters
     * @return JsonResponse JSON response with feedback data
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Feedback::with(['submission.assignment', 'submission.student', 'teacher']);

        // Role-based filtering
        if ($user->hasRole('student')) {
            // Students can only see feedback for their submissions
            $query->whereHas('submission', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            });
        } elseif ($user->hasRole('teacher')) {
            // Teachers can see feedback they created or for their assignments
            $query->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhereHas('submission.assignment', function ($subQ) use ($user) {
                      $subQ->where('teacher_id', $user->id);
                  });
            });
        }
        // Admins can see all feedback

        // Filter by submission if provided
        if ($request->has('submission_id')) {
            $query->where('submission_id', $request->get('submission_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by area
        if ($request->has('area')) {
            $query->where('area', $request->get('area'));
        }

        $feedback = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $feedback->items(),
            'meta' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total()
            ]
        ]);
    }

    /**
     * Get feedback visible to the authenticated student.
     *
     * @param Request $request HTTP request with optional filters
     * @return JsonResponse JSON response with student feedback data
     */
    public function studentFeedback(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('student')) {
            return response()->json([
                'success' => false,
                'message' => 'Only students can access this resource'
            ], 403);
        }

        $query = Feedback::with(['submission.assignment', 'submission.student', 'teacher'])
            ->whereHas('submission', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('area')) {
            $query->where('area', $request->get('area'));
        }

        if ($request->filled('assignment_id')) {
            $query->whereHas('submission.assignment', function ($q) use ($request) {
                $q->where('id', $request->get('assignment_id'));
            });
        }

        $feedback = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $feedback->getCollection()->transform(function (Feedback $feedback) {
            return $feedback->toFrontendArray();
        });

        return response()->json([
            'success' => true,
            'data' => $feedback->items(),
            'meta' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total()
            ]
        ]);
    }

    /**
     * Store a newly created feedback with AI analysis.
     *
     * @param Request $request HTTP request with feedback data
     * @return JsonResponse JSON response with created feedback
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'submission_id' => 'required|exists:submissions,id',
            'type' => ['required', Rule::in(array_keys(Feedback::TYPES))],
            'area' => ['required', Rule::in(array_keys(Feedback::AREAS))],
            'content' => 'required_unless:is_ai_generated,true|string|max:2000',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:20480', // 20MB max
            'score' => 'nullable|integer|min:0|max:100',
            'priority' => ['sometimes', Rule::in(array_keys(Feedback::PRIORITIES))],
            'recommendations' => 'nullable|array',
            'recommendations.*' => 'string|max:500',
            'is_ai_generated' => 'sometimes|boolean',
            'generate_audio' => 'sometimes|boolean'
        ]);

        $submission = Submission::findOrFail($validated['submission_id']);
        $this->authorize('createFeedback', $submission);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $feedbackData = [
                'submission_id' => $submission->id,
                'teacher_id' => $user->id,
                'type' => $validated['type'],
                'area' => $validated['area'],
                'score' => $validated['score'] ?? null,
                'priority' => $validated['priority'] ?? Feedback::PRIORITY_MEDIUM,
                'recommendations' => $validated['recommendations'] ?? [],
                'is_ai_generated' => $validated['is_ai_generated'] ?? false,
                'status' => Feedback::STATUS_DRAFT
            ];

            // Handle AI-generated feedback
            if ($validated['is_ai_generated'] ?? false) {
                $aiFeedback = $this->generateFeedbackAnalysis($submission, $validated['area']);
                $feedbackData['content'] = $aiFeedback['content'];
                $feedbackData['ai_analysis'] = json_encode($aiFeedback['analysis']);
                $feedbackData['score'] = $aiFeedback['analysis']['accuracy_score'] ?? null;
            } else {
                $feedbackData['content'] = $validated['content'];
            }

            // Handle audio upload
            if ($request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store('feedback/audio', 'public');
                $feedbackData['audio_url'] = Storage::url($audioPath);
                $feedbackData['audio_duration'] = $this->getAudioDuration($audioPath);
            }

            // Generate audio feedback if requested
            if ($validated['generate_audio'] ?? false && !empty($feedbackData['content'])) {
                try {
                    $audioPath = $this->whisperService->generateAudioFeedback($feedbackData['content']);
                    $feedbackData['audio_url'] = Storage::url($audioPath);
                } catch (\Exception $e) {
                    // Log warning but continue without audio
                    \Log::warning('Audio feedback generation failed', [
                        'submission_id' => $submission->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $feedback = Feedback::create($feedbackData);

            // Update submission status if this is the first feedback
            if ($submission->feedback()->count() === 1) {
                $submission->update(['status' => Submission::STATUS_REVIEWED]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback created successfully',
                'data' => $feedback->fresh()->toFrontendArray()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create feedback: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified feedback.
     *
     * @param Feedback $feedback The feedback to display
     * @return JsonResponse JSON response with feedback data
     */
    public function show(Feedback $feedback): JsonResponse
    {
        $this->authorize('view', $feedback);

        $feedback->load(['submission.assignment', 'submission.student', 'teacher']);

        return response()->json([
            'success' => true,
            'data' => $feedback->toFrontendArray()
        ]);
    }

    /**
     * Update the specified feedback.
     *
     * @param Request $request HTTP request with updated feedback data
     * @param Feedback $feedback The feedback to update
     * @return JsonResponse JSON response with updated feedback
     */
    public function update(Request $request, Feedback $feedback): JsonResponse
    {
        $this->authorize('update', $feedback);

        $validated = $request->validate([
            'type' => ['sometimes', Rule::in(array_keys(Feedback::TYPES))],
            'area' => ['sometimes', Rule::in(array_keys(Feedback::AREAS))],
            'content' => 'sometimes|string|max:2000',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:20480',
            'score' => 'nullable|integer|min:0|max:100',
            'priority' => ['sometimes', Rule::in(array_keys(Feedback::PRIORITIES))],
            'recommendations' => 'nullable|array',
            'recommendations.*' => 'string|max:500',
            'status' => ['sometimes', Rule::in(array_keys(Feedback::STATUSES))]
        ]);

        DB::beginTransaction();
        try {
            $updateData = [];

            foreach (['type', 'area', 'content', 'score', 'priority', 'recommendations', 'status'] as $field) {
                if (isset($validated[$field])) {
                    $updateData[$field] = $validated[$field];
                }
            }

            // Handle audio upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file
                if ($feedback->audio_url && Storage::exists($feedback->audio_url)) {
                    Storage::delete($feedback->audio_url);
                }

                $audioPath = $request->file('audio_file')->store('feedback/audio', 'public');
                $updateData['audio_url'] = Storage::url($audioPath);
                $updateData['audio_duration'] = $this->getAudioDuration($audioPath);
            }

            $feedback->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback updated successfully',
                'data' => $feedback->fresh()->toFrontendArray()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update feedback: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified feedback.
     *
     * @param Feedback $feedback The feedback to delete
     * @return JsonResponse JSON response confirming deletion
     */
    public function destroy(Feedback $feedback): JsonResponse
    {
        $this->authorize('delete', $feedback);

        DB::beginTransaction();
        try {
            // Delete associated audio file
            if ($feedback->audio_url && Storage::exists($feedback->audio_url)) {
                Storage::delete($feedback->audio_url);
            }

            $feedback->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete feedback: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish feedback to make it visible to students.
     *
     * @param Feedback $feedback The feedback to publish
     * @return JsonResponse JSON response confirming publication
     */
    public function publish(Feedback $feedback): JsonResponse
    {
        $this->authorize('update', $feedback);

        if ($feedback->status === Feedback::STATUS_PUBLISHED) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback is already published'
            ], 422);
        }

        $feedback->publish();

        // Create notification for student
        $feedback->submission->assignment->createNotification(
            $feedback->submission->student_id,
            'feedback_received',
            "New feedback received for assignment: {$feedback->submission->assignment->title}",
            ['feedback_id' => $feedback->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback published successfully',
            'data' => $feedback->fresh()->toFrontendArray()
        ]);
    }

    /**
     * Archive feedback.
     *
     * @param Feedback $feedback The feedback to archive
     * @return JsonResponse JSON response confirming archival
     */
    public function archive(Feedback $feedback): JsonResponse
    {
        $this->authorize('update', $feedback);

        $feedback->archive();

        return response()->json([
            'success' => true,
            'message' => 'Feedback archived successfully',
            'data' => $feedback->fresh()->toFrontendArray()
        ]);
    }

    /**
     * Generate AI feedback for a submission.
     *
     * @param Request $request HTTP request with submission data
     * @return JsonResponse JSON response with AI-generated feedback
     */
    public function generateAIFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'submission_id' => 'required|exists:submissions,id',
            'areas' => 'nullable|array',
            'areas.*' => Rule::in(array_keys(Feedback::AREAS))
        ]);

        $submission = Submission::findOrFail($validated['submission_id']);
        $this->authorize('createFeedback', $submission);

        if (!$submission->hasAudio()) {
            return response()->json([
                'success' => false,
                'message' => 'Submission must have audio for AI analysis'
            ], 422);
        }

        try {
            $areas = $validated['areas'] ?? array_keys(Feedback::AREAS);
            $aiAnalysis = $submission->processAIAnalysis($areas);

            $feedbackItems = [];
            foreach ($aiAnalysis as $area => $analysis) {
                $feedback = Feedback::createFromAIAnalysis($submission, $area, $analysis);
                $feedbackItems[] = $feedback->toFrontendArray();
            }

            return response()->json([
                'success' => true,
                'message' => 'AI feedback generated successfully',
                'data' => $feedbackItems
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI feedback: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feedback analytics for teachers.
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

        $query = Feedback::query();

        // Filter by teacher's feedback if not admin
        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->get('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->get('end_date'));
        }

        $analytics = [
            'total_feedback' => $query->count(),
            'published_feedback' => $query->where('status', Feedback::STATUS_PUBLISHED)->count(),
            'draft_feedback' => $query->where('status', Feedback::STATUS_DRAFT)->count(),
            'ai_generated_feedback' => $query->where('is_ai_generated', true)->count(),
            'feedback_by_type' => $query->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type'),
            'feedback_by_area' => $query->groupBy('area')
                ->selectRaw('area, count(*) as count')
                ->pluck('count', 'area'),
            'average_score' => $query->whereNotNull('score')->avg('score'),
            'feedback_by_day' => $query->selectRaw('DATE(created_at) as date, count(*) as count')
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
     * Generate structured AI feedback for a submission area.
     *
     * @param Submission $submission The submission to analyze
     * @param string $area The feedback area to focus on
     * @return array AI-generated feedback content and analysis
     */
    private function generateFeedbackAnalysis(Submission $submission, string $area): array
    {
        try {
            // Use WhisperService to analyze the submission audio
            $analysis = $this->whisperService->analyzeRecitation($submission->audio_url, [
                'area' => $area,
                'assignment_type' => $submission->assignment->type,
                'verses' => $submission->assignment->verses ?? []
            ]);

                $content = $this->generateFeedbackContent($analysis, $area);

            return [
                'content' => $content,
                'analysis' => $analysis
            ];
        } catch (\Exception $e) {
            \Log::error('AI feedback generation failed', [
                'submission_id' => $submission->id,
                'area' => $area,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Failed to generate AI feedback: ' . $e->getMessage());
        }
    }

    /**
     * Generate human-readable feedback content from AI analysis.
     *
     * @param array $analysis AI analysis results
     * @param string $area Feedback area
     * @return string Generated feedback content
     */
    private function generateFeedbackContent(array $analysis, string $area): string
    {
        $content = [];
        
        switch ($area) {
            case 'tajweed':
                if (isset($analysis['tajweed_score'])) {
                    $score = $analysis['tajweed_score'];
                    $content[] = "Tajweed Score: {$score}/100";
                    
                    if ($score >= 90) {
                        $content[] = "Excellent tajweed application! Your pronunciation rules are very accurate.";
                    } elseif ($score >= 70) {
                        $content[] = "Good tajweed application with room for improvement in some areas.";
                    } else {
                        $content[] = "Focus on improving tajweed rules application.";
                    }
                }
                break;
                
            case 'fluency':
                if (isset($analysis['fluency_score'])) {
                    $score = $analysis['fluency_score'];
                    $content[] = "Fluency Score: {$score}/100";
                    
                    if (isset($analysis['pace_analysis'])) {
                        $content[] = "Recitation pace: " . $analysis['pace_analysis'];
                    }
                }
                break;
                
            case 'accuracy':
                if (isset($analysis['accuracy_score'])) {
                    $score = $analysis['accuracy_score'];
                    $content[] = "Accuracy Score: {$score}/100";
                    
                    if (isset($analysis['errors']) && !empty($analysis['errors'])) {
                        $content[] = "Areas for improvement:";
                        foreach ($analysis['errors'] as $error) {
                            $content[] = "- " . $error;
                        }
                    }
                }
                break;
        }
        
        if (isset($analysis['recommendations']) && !empty($analysis['recommendations'])) {
            $content[] = "\nRecommendations:";
            foreach ($analysis['recommendations'] as $recommendation) {
                $content[] = "- " . $recommendation;
            }
        }
        
        return implode("\n", $content);
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