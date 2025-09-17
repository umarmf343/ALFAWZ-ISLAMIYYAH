<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Feedback;
use App\Services\WhisperService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AIFeedbackController extends Controller
{
    protected WhisperService $whisperService;

    public function __construct(WhisperService $whisperService)
    {
        $this->whisperService = $whisperService;
    }

    /**
     * Generate AI feedback for a submission using Whisper analysis.
     *
     * @param Request $request HTTP request with submission data
     * @return JsonResponse AI feedback analysis results
     */
    public function generateFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'submission_id' => 'required|exists:submissions,id',
            'audio_url' => 'required|url',
            'expected_text' => 'nullable|string',
            'assignment_type' => 'nullable|string|in:memorization,tajweed,recitation,general',
            'area' => 'nullable|string|in:tajweed,fluency,accuracy,general'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $submission = Submission::findOrFail($request->submission_id);
            
            // Check if user can access this submission
            $user = $request->user();
            if (!$this->canAccessSubmission($user, $submission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to submission'
                ], 403);
            }

            // Check cache for recent analysis
            $cacheKey = "ai_feedback_{$submission->id}_" . md5($request->audio_url . $request->expected_text);
            $cachedFeedback = Cache::get($cacheKey);
            
            if ($cachedFeedback) {
                return response()->json([
                    'success' => true,
                    'feedback' => $cachedFeedback,
                    'cached' => true
                ]);
            }

            // Prepare analysis options
            $options = [
                'assignment_type' => $request->assignment_type ?? 'general',
                'area' => $request->area ?? 'general'
            ];

            // Add expected text if provided
            if ($request->expected_text) {
                $options['verses'] = [$request->expected_text];
            }

            // Generate AI feedback
            $feedback = $this->whisperService->analyzeRecitation(
                $request->audio_url,
                $options
            );

            // Enhance feedback with additional context
            $feedback = $this->enhanceFeedbackWithContext($feedback, $submission);

            // Cache the result for 1 hour
            Cache::put($cacheKey, $feedback, now()->addHour());

            // Log the analysis for monitoring
            Log::info('AI feedback generated', [
                'submission_id' => $submission->id,
                'user_id' => $user->id,
                'accuracy_score' => $feedback['accuracy_score'] ?? null,
                'analysis_area' => $options['area']
            ]);

            return response()->json([
                'success' => true,
                'feedback' => $feedback,
                'cached' => false
            ]);

        } catch (Exception $e) {
            Log::error('AI feedback generation failed', [
                'submission_id' => $request->submission_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI feedback',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get AI feedback history for a submission.
     *
     * @param Submission $submission The submission model
     * @return JsonResponse Feedback history
     */
    public function getFeedbackHistory(Submission $submission): JsonResponse
    {
        $user = request()->user();
        
        if (!$this->canAccessSubmission($user, $submission)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to submission'
            ], 403);
        }

        // Get all feedback for this submission
        $feedback = Feedback::where('submission_id', $submission->id)
            ->with('teacher:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'note' => $item->note,
                    'audio_s3_url' => $item->audio_s3_url,
                    'teacher' => $item->teacher,
                    'created_at' => $item->created_at,
                    'type' => 'manual'
                ];
            });

        return response()->json([
            'success' => true,
            'feedback_history' => $feedback
        ]);
    }

    /**
     * Regenerate AI feedback with different parameters.
     *
     * @param Request $request HTTP request
     * @param Submission $submission The submission model
     * @return JsonResponse Regenerated feedback
     */
    public function regenerateFeedback(Request $request, Submission $submission): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio_url' => 'required|url',
            'expected_text' => 'nullable|string',
            'assignment_type' => 'nullable|string|in:memorization,tajweed,recitation,general',
            'area' => 'nullable|string|in:tajweed,fluency,accuracy,general',
            'force_regenerate' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        if (!$this->canAccessSubmission($user, $submission)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to submission'
            ], 403);
        }

        try {
            // Clear cache if force regenerate is requested
            if ($request->boolean('force_regenerate')) {
                $cacheKey = "ai_feedback_{$submission->id}_" . md5($request->audio_url . $request->expected_text);
                Cache::forget($cacheKey);
            }

            // Use the same logic as generateFeedback
            $request->merge(['submission_id' => $submission->id]);
            return $this->generateFeedback($request);

        } catch (Exception $e) {
            Log::error('AI feedback regeneration failed', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate AI feedback',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get AI analysis capabilities and supported features.
     *
     * @return JsonResponse Available capabilities
     */
    public function getCapabilities(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'capabilities' => [
                'supported_languages' => ['ar', 'en'],
                'analysis_areas' => [
                    'tajweed' => 'Tajweed rules and pronunciation analysis',
                    'fluency' => 'Recitation pace and flow analysis',
                    'accuracy' => 'Text accuracy and pronunciation errors',
                    'general' => 'Overall recitation quality assessment'
                ],
                'assignment_types' => [
                    'memorization' => 'Focus on accuracy and fluency',
                    'tajweed' => 'Focus on tajweed rules application',
                    'recitation' => 'Comprehensive recitation analysis',
                    'general' => 'Basic recitation assessment'
                ],
                'features' => [
                    'audio_transcription' => true,
                    'tajweed_analysis' => true,
                    'fluency_scoring' => true,
                    'pace_analysis' => true,
                    'pronunciation_errors' => true,
                    'text_comparison' => true,
                    'audio_feedback_generation' => true
                ],
                'supported_audio_formats' => ['mp3', 'wav', 'm4a', 'ogg'],
                'max_audio_duration' => 600, // 10 minutes
                'cache_duration' => 3600 // 1 hour
            ]
        ]);
    }

    /**
     * Check if user can access the submission.
     *
     * @param mixed $user The authenticated user
     * @param Submission $submission The submission to check
     * @return bool Whether user can access submission
     */
    private function canAccessSubmission($user, Submission $submission): bool
    {
        // Student can access their own submissions
        if ($submission->student_id === $user->id) {
            return true;
        }

        // Teacher can access submissions from their assignments
        if ($submission->assignment && $submission->assignment->teacher_id === $user->id) {
            return true;
        }

        // Admin can access all submissions
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Enhance feedback with additional context from submission.
     *
     * @param array $feedback Base AI feedback
     * @param Submission $submission The submission model
     * @return array Enhanced feedback
     */
    private function enhanceFeedbackWithContext(array $feedback, Submission $submission): array
    {
        // Add submission context
        $feedback['submission_context'] = [
            'submission_id' => $submission->id,
            'assignment_title' => $submission->assignment->title ?? 'Unknown Assignment',
            'student_name' => $submission->student->name ?? 'Unknown Student',
            'submitted_at' => $submission->created_at,
            'current_score' => $submission->score
        ];

        // Add hasanat calculation if applicable
        if (isset($feedback['transcribed_text'])) {
            $feedback['hasanat_earned'] = $this->calculateHasanat($feedback['transcribed_text']);
        }

        // Add improvement suggestions based on score
        $feedback['improvement_suggestions'] = $this->generateImprovementSuggestions($feedback);

        // Add confidence level
        $feedback['confidence_level'] = $this->calculateConfidenceLevel($feedback);

        return $feedback;
    }

    /**
     * Calculate hasanat from Arabic text.
     *
     * @param string $text Arabic text
     * @return int Hasanat count
     */
    private function calculateHasanat(string $text): int
    {
        // Remove diacritics and normalize
        $normalized = preg_replace('/[\u064B-\u065F\u0670\u06D6-\u06ED]/u', '', $text);
        
        // Count Arabic letters
        $letterCount = preg_match_all('/[\u0621-\u064A]/u', $normalized);
        
        // Each letter = 10 hasanat
        return $letterCount * 10;
    }

    /**
     * Generate improvement suggestions based on feedback scores.
     *
     * @param array $feedback AI feedback data
     * @return array Improvement suggestions
     */
    private function generateImprovementSuggestions(array $feedback): array
    {
        $suggestions = [];
        
        $accuracyScore = $feedback['accuracy_score'] ?? 0;
        $tajweedScore = $feedback['tajweed_score'] ?? 0;
        $fluencyScore = $feedback['fluency_score'] ?? 0;
        
        if ($accuracyScore < 70) {
            $suggestions[] = 'Focus on memorization and text accuracy';
        }
        
        if ($tajweedScore < 70) {
            $suggestions[] = 'Practice tajweed rules with a qualified teacher';
        }
        
        if ($fluencyScore < 70) {
            $suggestions[] = 'Work on recitation pace and flow';
        }
        
        if (empty($suggestions)) {
            $suggestions[] = 'Continue practicing to maintain excellent performance';
        }
        
        return $suggestions;
    }

    /**
     * Calculate confidence level of the analysis.
     *
     * @param array $feedback AI feedback data
     * @return string Confidence level
     */
    private function calculateConfidenceLevel(array $feedback): string
    {
        $duration = $feedback['audio_duration'] ?? 0;
        $hasExpectedText = !empty($feedback['expected_text']);
        
        if ($duration < 5) {
            return 'low'; // Very short audio
        }
        
        if ($duration > 30 && $hasExpectedText) {
            return 'high'; // Good duration with reference text
        }
        
        if ($hasExpectedText) {
            return 'medium'; // Has reference text
        }
        
        return 'low'; // No reference text
    }
}