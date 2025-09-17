<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AdminWhisperController monitors Whisper job processing and analytics.
 * Provides job status tracking, error analysis, and performance metrics.
 */
class AdminWhisperController extends Controller
{
    /**
     * Get paginated list of Whisper jobs with filtering and search.
     * Includes user info, job status, and processing metrics.
     *
     * @param Request $request HTTP request with filtering parameters
     * @return \Illuminate\Http\JsonResponse paginated Whisper jobs list
     */
    public function index(Request $request)
    {
        $query = DB::table('whisper_jobs')
            ->leftJoin('users', 'whisper_jobs.user_id', '=', 'users.id')
            ->select([
                'whisper_jobs.id',
                'whisper_jobs.user_id',
                'users.name as user_name',
                'users.email as user_email',
                'whisper_jobs.file_url',
                'whisper_jobs.meta_json',
                'whisper_jobs.status',
                'whisper_jobs.feedback_json',
                'whisper_jobs.error',
                'whisper_jobs.created_at',
                'whisper_jobs.updated_at'
            ]);
        
        // Search by user name, email, or job ID
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('whisper_jobs.id', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('whisper_jobs.status', $status);
        }
        
        // Filter by user
        if ($userId = $request->get('user_id')) {
            $query->where('whisper_jobs.user_id', $userId);
        }
        
        // Filter by date range
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('whisper_jobs.created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('whisper_jobs.created_at', '<=', $endDate);
        }
        
        // Filter by error status
        if ($hasError = $request->get('has_error')) {
            if ($hasError === 'true') {
                $query->whereNotNull('whisper_jobs.error');
            } elseif ($hasError === 'false') {
                $query->whereNull('whisper_jobs.error');
            }
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Handle sorting by user name
        if ($sortBy === 'user_name') {
            $query->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy("whisper_jobs.{$sortBy}", $sortOrder);
        }
        
        // Manual pagination
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $total = $query->count();
        $jobs = $query->offset($offset)->limit($perPage)->get();
        
        // Transform data to include parsed metadata and processing time
        $transformedJobs = $jobs->map(function ($job) {
            $meta = json_decode($job->meta_json, true) ?? [];
            $feedback = json_decode($job->feedback_json, true) ?? [];
            
            // Calculate processing time if completed
            $processingTime = null;
            if ($job->status === 'completed' && isset($meta['started_at'])) {
                $startTime = \Carbon\Carbon::parse($meta['started_at']);
                $endTime = \Carbon\Carbon::parse($job->updated_at);
                $processingTime = $startTime->diffInSeconds($endTime);
            }
            
            return [
                'id' => $job->id,
                'user' => [
                    'id' => $job->user_id,
                    'name' => $job->user_name,
                    'email' => $job->user_email,
                ],
                'file_url' => $job->file_url,
                'status' => $job->status,
                'meta' => $meta,
                'feedback_summary' => $this->summarizeFeedback($feedback),
                'has_error' => !empty($job->error),
                'error_preview' => $job->error ? substr($job->error, 0, 100) . '...' : null,
                'processing_time_seconds' => $processingTime,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
            ];
        });
        
        // Build pagination response
        $lastPage = ceil($total / $perPage);
        
        return response()->json([
            'data' => $transformedJobs,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ]);
    }
    
    /**
     * Get detailed view of a specific Whisper job.
     * Includes full metadata, feedback JSON, and error details.
     *
     * @param string $id Whisper job ID (UUID)
     * @return \Illuminate\Http\JsonResponse detailed job data
     */
    public function show($id)
    {
        $job = DB::table('whisper_jobs')
            ->leftJoin('users', 'whisper_jobs.user_id', '=', 'users.id')
            ->where('whisper_jobs.id', $id)
            ->select([
                'whisper_jobs.*',
                'users.name as user_name',
                'users.email as user_email'
            ])
            ->first();
        
        if (!$job) {
            return response()->json(['error' => 'Whisper job not found'], 404);
        }
        
        $meta = json_decode($job->meta_json, true) ?? [];
        $feedback = json_decode($job->feedback_json, true) ?? [];
        
        // Calculate processing metrics
        $processingMetrics = $this->calculateProcessingMetrics($job, $meta);
        
        return response()->json([
            'id' => $job->id,
            'user' => [
                'id' => $job->user_id,
                'name' => $job->user_name,
                'email' => $job->user_email,
            ],
            'file_url' => $job->file_url,
            'status' => $job->status,
            'meta' => $meta,
            'feedback' => $feedback,
            'feedback_analysis' => $this->analyzeFeedback($feedback),
            'error' => $job->error,
            'processing_metrics' => $processingMetrics,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
        ]);
    }
    
    /**
     * Get Whisper job analytics and performance metrics.
     * Provides insights into job processing patterns and success rates.
     *
     * @param Request $request HTTP request with optional date filters
     * @return \Illuminate\Http\JsonResponse analytics data
     */
    public function analytics(Request $request)
    {
        $query = DB::table('whisper_jobs');
        
        // Apply date filters
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        // Overall metrics
        $totalJobs = $query->count();
        $completedJobs = (clone $query)->where('status', 'completed')->count();
        $failedJobs = (clone $query)->where('status', 'failed')->count();
        $pendingJobs = (clone $query)->where('status', 'pending')->count();
        $processingJobs = (clone $query)->where('status', 'processing')->count();
        
        // Success rate
        $successRate = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) : 0;
        
        // Status distribution
        $statusDistribution = $query->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Daily job trend (last 30 days)
        $dailyTrend = collect(range(0, 29))->map(function ($daysAgo) use ($query) {
            $date = now()->subDays($daysAgo)->toDateString();
            $dayQuery = clone $query;
            $total = $dayQuery->whereDate('created_at', $date)->count();
            $completed = $dayQuery->whereDate('created_at', $date)
                                 ->where('status', 'completed')->count();
            
            return [
                'date' => $date,
                'total_jobs' => $total,
                'completed_jobs' => $completed,
                'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        })->reverse()->values();
        
        // Average processing time for completed jobs
        $avgProcessingTime = $this->calculateAverageProcessingTime($query);
        
        // Top error types
        $topErrors = (clone $query)
            ->whereNotNull('error')
            ->select(DB::raw('LEFT(error, 100) as error_preview'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('LEFT(error, 100)'))
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($error) {
                return [
                    'error_preview' => $error->error_preview,
                    'count' => $error->count,
                ];
            });
        
        return response()->json([
            'summary' => [
                'total_jobs' => $totalJobs,
                'completed_jobs' => $completedJobs,
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
                'processing_jobs' => $processingJobs,
                'success_rate' => $successRate,
                'avg_processing_time_seconds' => $avgProcessingTime,
            ],
            'status_distribution' => $statusDistribution,
            'daily_trend' => $dailyTrend,
            'top_errors' => $topErrors,
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Summarize feedback for quick overview.
     * @param array $feedback Parsed feedback data
     * @return array summary with key metrics
     */
    private function summarizeFeedback(array $feedback): array
    {
        if (empty($feedback)) {
            return ['has_feedback' => false];
        }
        
        return [
            'has_feedback' => true,
            'overall_score' => $feedback['overall_score'] ?? null,
            'transcript_length' => isset($feedback['transcript']) 
                ? strlen($feedback['transcript']) 
                : null,
            'rubric_criteria_count' => isset($feedback['rubric']) 
                ? count($feedback['rubric']) 
                : 0,
        ];
    }
    
    /**
     * Analyze feedback for detailed breakdown.
     * @param array $feedback Parsed feedback data
     * @return array detailed analysis
     */
    private function analyzeFeedback(array $feedback): array
    {
        if (empty($feedback)) {
            return ['has_analysis' => false];
        }
        
        $analysis = ['has_analysis' => true];
        
        // Transcript analysis
        if (isset($feedback['transcript'])) {
            $transcript = $feedback['transcript'];
            $analysis['transcript'] = [
                'length' => strlen($transcript),
                'word_count' => str_word_count($transcript),
                'has_content' => !empty(trim($transcript)),
            ];
        }
        
        // Rubric analysis
        if (isset($feedback['rubric']) && is_array($feedback['rubric'])) {
            $rubric = $feedback['rubric'];
            $scores = collect($rubric)->pluck('score')->filter()->values();
            
            $analysis['rubric'] = [
                'total_criteria' => count($rubric),
                'scored_criteria' => $scores->count(),
                'avg_score' => $scores->count() > 0 ? round($scores->avg(), 1) : null,
                'score_range' => $scores->count() > 0 
                    ? ['min' => $scores->min(), 'max' => $scores->max()]
                    : null,
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Calculate processing metrics for a job.
     * @param object $job Job data
     * @param array $meta Job metadata
     * @return array processing metrics
     */
    private function calculateProcessingMetrics($job, array $meta): array
    {
        $metrics = [];
        
        // Processing time
        if ($job->status === 'completed' && isset($meta['started_at'])) {
            $startTime = \Carbon\Carbon::parse($meta['started_at']);
            $endTime = \Carbon\Carbon::parse($job->updated_at);
            $metrics['processing_time_seconds'] = $startTime->diffInSeconds($endTime);
            $metrics['processing_time_human'] = $startTime->diffForHumans($endTime, true);
        }
        
        // Queue time (time between creation and processing start)
        if (isset($meta['started_at'])) {
            $createdTime = \Carbon\Carbon::parse($job->created_at);
            $startTime = \Carbon\Carbon::parse($meta['started_at']);
            $metrics['queue_time_seconds'] = $createdTime->diffInSeconds($startTime);
        }
        
        // File size if available
        if (isset($meta['file_size_bytes'])) {
            $metrics['file_size_bytes'] = $meta['file_size_bytes'];
            $metrics['file_size_human'] = $this->formatBytes($meta['file_size_bytes']);
        }
        
        return $metrics;
    }
    
    /**
     * Calculate average processing time for completed jobs.
     * @param \Illuminate\Database\Query\Builder $query Base query
     * @return float|null average processing time in seconds
     */
    private function calculateAverageProcessingTime($query): ?float
    {
        $completedJobs = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('meta_json')
            ->get(['meta_json', 'created_at', 'updated_at']);
        
        $processingTimes = $completedJobs->map(function ($job) {
            $meta = json_decode($job->meta_json, true) ?? [];
            if (!isset($meta['started_at'])) {
                return null;
            }
            
            $startTime = \Carbon\Carbon::parse($meta['started_at']);
            $endTime = \Carbon\Carbon::parse($job->updated_at);
            return $startTime->diffInSeconds($endTime);
        })->filter();
        
        return $processingTimes->count() > 0 
            ? round($processingTimes->avg(), 1)
            : null;
    }
    
    /**
     * Format bytes into human-readable format.
     * @param int $bytes File size in bytes
     * @return string formatted size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
}