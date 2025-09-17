<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers;

use App\Http\Requests\TajweedAnalysisRequest;
use App\Jobs\ProcessTajweedAnalysis;
use App\Models\OrgSetting;
use App\Models\Recitation;
use App\Models\WhisperJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TajweedController extends Controller
{
    /**
     * Submit audio for Tajweed analysis (Student role).
     */
    public function analyzeRecitation(TajweedAnalysisRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        // Check if Tajweed is enabled for this user
        if (!$this->isTajweedEnabledForUser($user)) {
            return response()->json([
                'error' => 'Tajweed analysis is not enabled for your account'
            ], 403);
        }

        // Check daily limit
        if (!$this->checkDailyLimit($user)) {
            return response()->json([
                'error' => 'Daily Tajweed analysis limit exceeded'
            ], 429);
        }

        // Validate audio duration
        $maxDuration = OrgSetting::getMaxAudioDuration();
        if ($request->duration_seconds > $maxDuration) {
            return response()->json([
                'error' => "Audio duration exceeds maximum of {$maxDuration} seconds"
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Store audio file
            $audioFile = $request->file('audio');
            $s3Key = 'tajweed/' . $user->id . '/' . Str::uuid() . '.' . $audioFile->getClientOriginalExtension();
            $path = Storage::disk('s3')->putFileAs('', $audioFile, $s3Key);

            // Create recitation record
            $recitation = Recitation::create([
                'user_id' => $user->id,
                'surah_id' => $request->surah_id,
                'ayah_from' => $request->ayah_from,
                'ayah_to' => $request->ayah_to,
                'expected_tokens' => $request->expected_tokens,
                's3_key' => $s3Key,
                'mime' => $audioFile->getMimeType(),
                'duration_seconds' => $request->duration_seconds,
                'tajweed_enabled' => true,
            ]);

            // Create Whisper job
            $job = WhisperJob::create([
                'recitation_id' => $recitation->id,
                'status' => 'pending',
            ]);

            // Dispatch analysis job
            ProcessTajweedAnalysis::dispatch($job->id);

            DB::commit();

            return response()->json([
                'message' => 'Audio submitted for Tajweed analysis',
                'recitation_id' => $recitation->id,
                'job_id' => $job->id,
                'estimated_completion' => now()->addMinutes(2)->toISOString(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to submit audio for analysis',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analysis status and results.
     */
    public function getAnalysisStatus(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        
        $job = WhisperJob::with('recitation')
            ->whereHas('recitation', function ($query) use ($user) {
                // Students can only see their own, teachers can see their students'
                if ($user->hasRole('student')) {
                    $query->where('user_id', $user->id);
                } elseif ($user->hasRole('teacher')) {
                    $query->whereIn('user_id', $user->students()->pluck('id')->push($user->id));
                }
                // Admins can see all
            })
            ->findOrFail($jobId);

        $response = [
            'job_id' => $job->id,
            'status' => $job->status,
            'created_at' => $job->created_at->toISOString(),
            'updated_at' => $job->updated_at->toISOString(),
        ];

        if ($job->isCompleted()) {
            $response['results'] = $job->getAnalysisResults();
        } elseif ($job->isFailed()) {
            $response['error'] = $job->error;
        }

        return response()->json($response);
    }

    /**
     * Get user's Tajweed analysis history.
     */
    public function getAnalysisHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = min($request->get('per_page', 20), 100);

        $query = WhisperJob::with(['recitation.user'])
            ->whereHas('recitation', function ($q) use ($user) {
                if ($user->hasRole('student')) {
                    $q->where('user_id', $user->id);
                } elseif ($user->hasRole('teacher')) {
                    $q->whereIn('user_id', $user->students()->pluck('id')->push($user->id));
                }
                // Admins see all
            })
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id') && $user->hasAnyRole(['teacher', 'admin'])) {
            $query->whereHas('recitation', fn($q) => $q->where('user_id', $request->user_id));
        }

        $jobs = $query->paginate($perPage);

        return response()->json([
            'data' => $jobs->items(),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ]
        ]);
    }

    /**
     * Get Tajweed analytics for teachers and admins.
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $days = min($request->get('days', 30), 90);
        $startDate = now()->subDays($days);

        $baseQuery = WhisperJob::with('recitation')
            ->where('created_at', '>=', $startDate);

        if ($user->hasRole('teacher')) {
            $baseQuery->whereHas('recitation', function ($q) use ($user) {
                $q->whereIn('user_id', $user->students()->pluck('id'));
            });
        }

        $analytics = [
            'total_analyses' => (clone $baseQuery)->count(),
            'completed_analyses' => (clone $baseQuery)->completed()->count(),
            'failed_analyses' => (clone $baseQuery)->failed()->count(),
            'pending_analyses' => (clone $baseQuery)->pending()->count(),
            'average_completion_time' => $this->getAverageCompletionTime($baseQuery),
            'daily_breakdown' => $this->getDailyBreakdown($baseQuery, $days),
            'top_users' => $this->getTopUsers($baseQuery),
        ];

        return response()->json($analytics);
    }

    /**
     * Update Tajweed settings (Admin only).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'default_enabled' => 'boolean',
            'daily_limit_per_user' => 'integer|min:1|max:1000',
            'max_audio_duration' => 'integer|min:30|max:1800',
            'retention_days' => 'integer|min:1|max:365',
        ]);

        OrgSetting::updateTajweedSettings($request->only([
            'default_enabled',
            'daily_limit_per_user', 
            'max_audio_duration',
            'retention_days'
        ]));

        return response()->json([
            'message' => 'Tajweed settings updated successfully',
            'settings' => OrgSetting::getTajweedSettings()
        ]);
    }

    /**
     * Get current Tajweed settings.
     */
    public function getSettings(): JsonResponse
    {
        return response()->json(OrgSetting::getTajweedSettings());
    }

    /**
     * Reprocess a failed analysis (Teacher/Admin only).
     */
    public function reprocessAnalysis(int $jobId): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $job = WhisperJob::findOrFail($jobId);
        
        if (!$job->isFailed()) {
            return response()->json([
                'error' => 'Job is not in failed state'
            ], 422);
        }

        $job->update([
            'status' => 'pending',
            'error' => null,
        ]);

        ProcessTajweedAnalysis::dispatch($job->id);

        return response()->json([
            'message' => 'Analysis requeued for processing',
            'job_id' => $job->id
        ]);
    }

    /**
     * Check if Tajweed is enabled for a user.
     */
    private function isTajweedEnabledForUser($user): bool
    {
        $userSettings = $user->settings ?? [];
        $userEnabled = $userSettings['tajweed_enabled'] ?? null;
        
        // User setting overrides org setting
        if ($userEnabled !== null) {
            return $userEnabled;
        }
        
        return OrgSetting::isTajweedEnabled();
    }

    /**
     * Check if user has exceeded daily limit.
     */
    private function checkDailyLimit($user): bool
    {
        $limit = OrgSetting::getTajweedDailyLimit();
        $today = now()->startOfDay();
        
        $todayCount = WhisperJob::whereHas('recitation', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('created_at', '>=', $today)->count();
        
        return $todayCount < $limit;
    }

    /**
     * Get average completion time for analytics.
     */
    private function getAverageCompletionTime($query): ?float
    {
        $completed = (clone $query)->completed()->get();
        
        if ($completed->isEmpty()) {
            return null;
        }
        
        $totalSeconds = $completed->sum(function ($job) {
            return $job->updated_at->diffInSeconds($job->created_at);
        });
        
        return round($totalSeconds / $completed->count(), 2);
    }

    /**
     * Get daily breakdown for analytics.
     */
    private function getDailyBreakdown($query, int $days): array
    {
        $breakdown = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $nextDate = $date->copy()->addDay();
            
            $count = (clone $query)
                ->where('created_at', '>=', $date)
                ->where('created_at', '<', $nextDate)
                ->count();
                
            $breakdown[] = [
                'date' => $date->toDateString(),
                'count' => $count
            ];
        }
        
        return $breakdown;
    }

    /**
     * Get top users for analytics.
     */
    private function getTopUsers($query): array
    {
        return (clone $query)
            ->select('recitations.user_id', DB::raw('COUNT(*) as analysis_count'))
            ->join('recitations', 'whisper_jobs.recitation_id', '=', 'recitations.id')
            ->groupBy('recitations.user_id')
            ->orderByDesc('analysis_count')
            ->limit(10)
            ->with(['recitation.user:id,name,email'])
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->recitation->user->name ?? 'Unknown',
                    'analysis_count' => $item->analysis_count
                ];
            })
            ->toArray();
    }
}