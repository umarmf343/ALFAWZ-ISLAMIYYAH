<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AdminSubmissionController provides submission monitoring and analytics.
 * Offers detailed filtering, rubric preview, and performance insights.
 */
class AdminSubmissionController extends Controller
{
    /**
     * Get paginated list of submissions with detailed filtering options.
     * Includes student, assignment, class info, and scoring metrics.
     *
     * @param Request $request HTTP request with filtering parameters
     * @return \Illuminate\Http\JsonResponse paginated submissions with metadata
     */
    public function index(Request $request)
    {
        $query = Submission::query()
            ->with([
                'user:id,name,email',
                'assignment:id,title,class_id',
                'assignment.class:id,name,level'
            ])
            ->select([
                'id', 'user_id', 'assignment_id', 'audio_url', 'transcript_preview',
                'overall_score', 'rubric_json', 'status', 'created_at', 'updated_at'
            ]);
        
        // Search by student name or assignment title
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('assignment', function ($assignmentQuery) use ($search) {
                    $assignmentQuery->where('title', 'like', "%{$search}%");
                });
            });
        }
        
        // Filter by class
        if ($classId = $request->get('class_id')) {
            $query->whereHas('assignment', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }
        
        // Filter by assignment
        if ($assignmentId = $request->get('assignment_id')) {
            $query->where('assignment_id', $assignmentId);
        }
        
        // Filter by student
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }
        
        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        
        // Filter by score range
        if ($minScore = $request->get('min_score')) {
            $query->where('overall_score', '>=', $minScore);
        }
        if ($maxScore = $request->get('max_score')) {
            $query->where('overall_score', '<=', $maxScore);
        }
        
        // Filter by date range
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $submissions = $query->paginate($request->get('per_page', 20));
        
        // Transform data to include parsed rubric and additional metadata
        $submissions->getCollection()->transform(function ($submission) {
            $rubric = json_decode($submission->rubric_json, true) ?? [];
            
            return [
                'id' => $submission->id,
                'student' => [
                    'id' => $submission->user->id,
                    'name' => $submission->user->name,
                    'email' => $submission->user->email,
                ],
                'assignment' => [
                    'id' => $submission->assignment->id,
                    'title' => $submission->assignment->title,
                    'class' => [
                        'id' => $submission->assignment->class->id,
                        'name' => $submission->assignment->class->name,
                        'level' => $submission->assignment->class->level,
                    ],
                ],
                'audio_url' => $submission->audio_url,
                'transcript_preview' => $submission->transcript_preview,
                'overall_score' => $submission->overall_score,
                'rubric' => $rubric,
                'rubric_summary' => $this->summarizeRubric($rubric),
                'status' => $submission->status,
                'created_at' => $submission->created_at->toISOString(),
                'updated_at' => $submission->updated_at->toISOString(),
            ];
        });
        
        return response()->json($submissions);
    }
    
    /**
     * Get detailed view of a specific submission.
     * Includes full rubric breakdown, audio analysis, and feedback history.
     *
     * @param int $id Submission ID
     * @return \Illuminate\Http\JsonResponse detailed submission data
     */
    public function show($id)
    {
        $submission = Submission::with([
            'user:id,name,email',
            'assignment:id,title,description,class_id',
            'assignment.class:id,name,level',
            'assignment.teacher:id,name',
            'feedbacks.teacher:id,name'
        ])->findOrFail($id);
        
        $rubric = json_decode($submission->rubric_json, true) ?? [];
        
        // Get submission rank within assignment
        $rank = Submission::where('assignment_id', $submission->assignment_id)
            ->whereNotNull('overall_score')
            ->where('overall_score', '>', $submission->overall_score ?? 0)
            ->count() + 1;
        
        $totalSubmissions = Submission::where('assignment_id', $submission->assignment_id)
            ->whereNotNull('overall_score')
            ->count();
        
        // Get class average for comparison
        $classAverage = Submission::where('assignment_id', $submission->assignment_id)
            ->whereNotNull('overall_score')
            ->avg('overall_score');
        
        return response()->json([
            'id' => $submission->id,
            'student' => [
                'id' => $submission->user->id,
                'name' => $submission->user->name,
                'email' => $submission->user->email,
            ],
            'assignment' => [
                'id' => $submission->assignment->id,
                'title' => $submission->assignment->title,
                'description' => $submission->assignment->description,
                'teacher' => [
                    'id' => $submission->assignment->teacher->id,
                    'name' => $submission->assignment->teacher->name,
                ],
                'class' => [
                    'id' => $submission->assignment->class->id,
                    'name' => $submission->assignment->class->name,
                    'level' => $submission->assignment->class->level,
                ],
            ],
            'audio_url' => $submission->audio_url,
            'transcript_preview' => $submission->transcript_preview,
            'overall_score' => $submission->overall_score,
            'rubric' => $rubric,
            'rubric_breakdown' => $this->analyzeRubric($rubric),
            'performance_context' => [
                'rank' => $rank,
                'total_submissions' => $totalSubmissions,
                'percentile' => $totalSubmissions > 0 
                    ? round((($totalSubmissions - $rank + 1) / $totalSubmissions) * 100, 1)
                    : null,
                'class_average' => $classAverage ? round($classAverage, 1) : null,
                'above_average' => $classAverage && $submission->overall_score 
                    ? $submission->overall_score > $classAverage
                    : null,
            ],
            'feedbacks' => $submission->feedbacks->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'teacher' => [
                        'id' => $feedback->teacher->id,
                        'name' => $feedback->teacher->name,
                    ],
                    'content' => $feedback->content,
                    'created_at' => $feedback->created_at->toISOString(),
                ];
            }),
            'status' => $submission->status,
            'created_at' => $submission->created_at->toISOString(),
            'updated_at' => $submission->updated_at->toISOString(),
        ]);
    }
    
    /**
     * Get submission analytics and trends.
     * Provides insights into submission patterns and performance metrics.
     *
     * @param Request $request HTTP request with optional filters
     * @return \Illuminate\Http\JsonResponse analytics data
     */
    public function analytics(Request $request)
    {
        $query = Submission::query();
        
        // Apply same filters as index for consistency
        if ($classId = $request->get('class_id')) {
            $query->whereHas('assignment', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }
        
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        // Overall metrics
        $totalSubmissions = $query->count();
        $avgScore = $query->whereNotNull('overall_score')->avg('overall_score');
        $completedSubmissions = $query->where('status', 'completed')->count();
        
        // Score distribution
        $scoreDistribution = $query->whereNotNull('overall_score')
            ->selectRaw('
                CASE 
                    WHEN overall_score >= 90 THEN "A (90-100)"
                    WHEN overall_score >= 80 THEN "B (80-89)"
                    WHEN overall_score >= 70 THEN "C (70-79)"
                    WHEN overall_score >= 60 THEN "D (60-69)"
                    ELSE "F (0-59)"
                END as grade_range,
                COUNT(*) as count
            ')
            ->groupBy('grade_range')
            ->pluck('count', 'grade_range')
            ->toArray();
        
        // Daily submission trend (last 30 days)
        $dailyTrend = collect(range(0, 29))->map(function ($daysAgo) use ($query) {
            $date = now()->subDays($daysAgo)->toDateString();
            $count = (clone $query)->whereDate('created_at', $date)->count();
            
            return [
                'date' => $date,
                'submissions' => $count,
            ];
        })->reverse()->values();
        
        return response()->json([
            'summary' => [
                'total_submissions' => $totalSubmissions,
                'completed_submissions' => $completedSubmissions,
                'completion_rate' => $totalSubmissions > 0 
                    ? round(($completedSubmissions / $totalSubmissions) * 100, 1)
                    : 0,
                'average_score' => $avgScore ? round($avgScore, 1) : null,
            ],
            'score_distribution' => $scoreDistribution,
            'daily_trend' => $dailyTrend,
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Summarize rubric for quick overview.
     * @param array $rubric Parsed rubric data
     * @return array summary with key metrics
     */
    private function summarizeRubric(array $rubric): array
    {
        if (empty($rubric)) {
            return ['total_criteria' => 0, 'avg_score' => null];
        }
        
        $scores = collect($rubric)->pluck('score')->filter()->values();
        
        return [
            'total_criteria' => count($rubric),
            'scored_criteria' => $scores->count(),
            'avg_score' => $scores->count() > 0 ? round($scores->avg(), 1) : null,
            'min_score' => $scores->count() > 0 ? $scores->min() : null,
            'max_score' => $scores->count() > 0 ? $scores->max() : null,
        ];
    }
    
    /**
     * bulkUpdate applies a shallow patch to many submission rows.
     * @param Request $request HTTP request with ids and patch data
     * @return \Illuminate\Http\JsonResponse Success response with count
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:submissions,id',
            'patch' => 'required|array',
        ]);

        // Whitelist allowed fields for bulk update
        $allowedFields = ['status', 'overall_score'];
        $patch = array_intersect_key($data['patch'], array_flip($allowedFields));
        
        if (empty($patch)) {
            return response()->json([
                'ok' => false, 
                'message' => 'No allowed fields provided for update'
            ], 422);
        }

        // Validate status if provided
        if (isset($patch['status']) && !in_array($patch['status'], ['pending', 'graded', 'returned', 'completed'])) {
            return response()->json([
                'ok' => false, 
                'message' => 'Invalid status value'
            ], 422);
        }

        // Validate score if provided
        if (isset($patch['overall_score']) && (!is_numeric($patch['overall_score']) || $patch['overall_score'] < 0 || $patch['overall_score'] > 100)) {
            return response()->json([
                'ok' => false, 
                'message' => 'Score must be between 0 and 100'
            ], 422);
        }

        DB::transaction(function () use ($data, $patch) {
            Submission::whereIn('id', $data['ids'])->update($patch);
            
            // Log audit trail
            if (function_exists('audit')) {
                audit('submission.bulk.update', [
                    'ids' => $data['ids'], 
                    'patch' => $patch,
                    'user_id' => auth()->id()
                ]);
            }
        });

        return response()->json([
            'ok' => true, 
            'count' => count($data['ids']),
            'message' => 'Submissions updated successfully'
        ]);
    }

    /**
     * Analyze rubric for detailed breakdown.
     * @param array $rubric Parsed rubric data
     * @return array detailed analysis
     */
    private function analyzeRubric(array $rubric): array
    {
        if (empty($rubric)) {
            return [];
        }
        
        return collect($rubric)->map(function ($criterion, $key) {
            return [
                'criterion' => $key,
                'score' => $criterion['score'] ?? null,
                'max_score' => $criterion['max_score'] ?? 100,
                'percentage' => isset($criterion['score'], $criterion['max_score']) && $criterion['max_score'] > 0
                    ? round(($criterion['score'] / $criterion['max_score']) * 100, 1)
                    : null,
                'feedback' => $criterion['feedback'] ?? null,
            ];
        })->values()->toArray();
    }
}