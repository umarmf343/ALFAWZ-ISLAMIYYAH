<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AdminLeaderboardController manages leaderboard analytics and rankings.
 * Provides insights into student performance and engagement metrics.
 */
class AdminLeaderboardController extends Controller
{
    /**
     * Get comprehensive leaderboard data with filtering options.
     * Includes global and class-specific rankings with performance metrics.
     *
     * @param Request $request HTTP request with filtering parameters
     * @return \Illuminate\Http\JsonResponse leaderboard data with analytics
     */
    public function index(Request $request)
    {
        $scope = $request->get('scope', 'global'); // global or class
        $period = $request->get('period', 'all_time'); // weekly, monthly, all_time
        $classId = $request->get('class_id');
        $limit = $request->get('limit', 50);
        
        // Build base query for submissions
        $submissionQuery = Submission::query()
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->whereNotNull('submissions.overall_score');
        
        // Apply period filter
        switch ($period) {
            case 'weekly':
                $submissionQuery->where('submissions.created_at', '>=', now()->startOfWeek());
                break;
            case 'monthly':
                $submissionQuery->where('submissions.created_at', '>=', now()->startOfMonth());
                break;
            // all_time has no additional filter
        }
        
        // Apply scope filter
        if ($scope === 'class' && $classId) {
            $submissionQuery->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                           ->where('assignments.class_id', $classId);
        }
        
        // Calculate rankings
        $leaderboard = $submissionQuery
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(submissions.id) as total_submissions'),
                DB::raw('AVG(submissions.overall_score) as avg_score'),
                DB::raw('MAX(submissions.overall_score) as best_score'),
                DB::raw('SUM(submissions.overall_score) as total_points'),
                DB::raw('MAX(submissions.created_at) as last_submission')
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('avg_score')
            ->orderByDesc('total_submissions')
            ->limit($limit)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'metrics' => [
                        'total_submissions' => $user->total_submissions,
                        'avg_score' => round($user->avg_score, 1),
                        'best_score' => round($user->best_score, 1),
                        'total_points' => round($user->total_points, 1),
                    ],
                    'last_submission' => $user->last_submission,
                    'status' => $this->getUserActivityStatus($user->last_submission),
                ];
            });
        
        // Get summary statistics
        $summary = $this->getLeaderboardSummary($submissionQuery, $period, $scope, $classId);
        
        return response()->json([
            'leaderboard' => $leaderboard,
            'summary' => $summary,
            'filters' => [
                'scope' => $scope,
                'period' => $period,
                'class_id' => $classId,
                'limit' => $limit,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Get detailed analytics for leaderboard performance.
     * Provides insights into score distributions and trends.
     *
     * @param Request $request HTTP request with filtering parameters
     * @return \Illuminate\Http\JsonResponse detailed analytics
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $classId = $request->get('class_id');
        
        // Score distribution analysis
        $scoreDistribution = $this->getScoreDistribution($period, $classId);
        
        // Performance trends over time
        $performanceTrends = $this->getPerformanceTrends($period, $classId);
        
        // Top performers analysis
        $topPerformers = $this->getTopPerformersAnalysis($period, $classId);
        
        // Engagement metrics
        $engagementMetrics = $this->getEngagementMetrics($period, $classId);
        
        return response()->json([
            'score_distribution' => $scoreDistribution,
            'performance_trends' => $performanceTrends,
            'top_performers' => $topPerformers,
            'engagement_metrics' => $engagementMetrics,
            'filters' => [
                'period' => $period,
                'class_id' => $classId,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Get class-specific leaderboard comparison.
     * Compares performance across different classes and levels.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse class comparison data
     */
    public function classComparison(Request $request)
    {
        $period = $request->get('period', 'monthly');
        
        // Build date filter
        $dateFilter = $this->buildDateFilter($period);
        
        $classStats = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->join('users as teachers', 'classes.teacher_id', '=', 'teachers.id')
            ->whereNotNull('submissions.overall_score')
            ->when($dateFilter, function ($query) use ($dateFilter) {
                return $query->where('submissions.created_at', '>=', $dateFilter);
            })
            ->select([
                'classes.id as class_id',
                'classes.name as class_name',
                'classes.level',
                'teachers.name as teacher_name',
                DB::raw('COUNT(DISTINCT submissions.user_id) as active_students'),
                DB::raw('COUNT(submissions.id) as total_submissions'),
                DB::raw('AVG(submissions.overall_score) as avg_score'),
                DB::raw('MAX(submissions.overall_score) as best_score'),
                DB::raw('MIN(submissions.overall_score) as lowest_score')
            ])
            ->groupBy('classes.id', 'classes.name', 'classes.level', 'teachers.name')
            ->orderBy('classes.level')
            ->orderByDesc('avg_score')
            ->get()
            ->map(function ($class) {
                return [
                    'class' => [
                        'id' => $class->class_id,
                        'name' => $class->class_name,
                        'level' => $class->level,
                        'teacher_name' => $class->teacher_name,
                    ],
                    'metrics' => [
                        'active_students' => $class->active_students,
                        'total_submissions' => $class->total_submissions,
                        'avg_score' => round($class->avg_score, 1),
                        'best_score' => round($class->best_score, 1),
                        'lowest_score' => round($class->lowest_score, 1),
                        'submissions_per_student' => $class->active_students > 0 
                            ? round($class->total_submissions / $class->active_students, 1)
                            : 0,
                    ],
                ];
            });
        
        // Calculate level averages
        $levelAverages = $classStats->groupBy('class.level')
            ->map(function ($classes, $level) {
                $avgScore = $classes->avg('metrics.avg_score');
                $totalStudents = $classes->sum('metrics.active_students');
                $totalSubmissions = $classes->sum('metrics.total_submissions');
                
                return [
                    'level' => $level,
                    'class_count' => $classes->count(),
                    'total_students' => $totalStudents,
                    'total_submissions' => $totalSubmissions,
                    'avg_score' => round($avgScore, 1),
                    'submissions_per_student' => $totalStudents > 0 
                        ? round($totalSubmissions / $totalStudents, 1)
                        : 0,
                ];
            })
            ->values();
        
        return response()->json([
            'class_stats' => $classStats,
            'level_averages' => $levelAverages,
            'period' => $period,
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Get leaderboard summary statistics.
     */
    private function getLeaderboardSummary($submissionQuery, $period, $scope, $classId): array
    {
        $totalParticipants = (clone $submissionQuery)
            ->distinct('users.id')
            ->count('users.id');
        
        $totalSubmissions = (clone $submissionQuery)->count();
        
        $avgScore = (clone $submissionQuery)->avg('submissions.overall_score');
        
        $scoreRange = (clone $submissionQuery)
            ->selectRaw('MIN(submissions.overall_score) as min_score, MAX(submissions.overall_score) as max_score')
            ->first();
        
        return [
            'total_participants' => $totalParticipants,
            'total_submissions' => $totalSubmissions,
            'avg_score' => $avgScore ? round($avgScore, 1) : null,
            'score_range' => [
                'min' => $scoreRange->min_score ? round($scoreRange->min_score, 1) : null,
                'max' => $scoreRange->max_score ? round($scoreRange->max_score, 1) : null,
            ],
            'submissions_per_participant' => $totalParticipants > 0 
                ? round($totalSubmissions / $totalParticipants, 1)
                : 0,
        ];
    }
    
    /**
     * Get score distribution for analytics.
     */
    private function getScoreDistribution($period, $classId): array
    {
        $query = Submission::query()->whereNotNull('overall_score');
        
        if ($dateFilter = $this->buildDateFilter($period)) {
            $query->where('created_at', '>=', $dateFilter);
        }
        
        if ($classId) {
            $query->whereHas('assignment', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }
        
        return $query->selectRaw('
            CASE 
                WHEN overall_score >= 90 THEN "90-100"
                WHEN overall_score >= 80 THEN "80-89"
                WHEN overall_score >= 70 THEN "70-79"
                WHEN overall_score >= 60 THEN "60-69"
                ELSE "0-59"
            END as score_range,
            COUNT(*) as count
        ')
        ->groupBy('score_range')
        ->pluck('count', 'score_range')
        ->toArray();
    }
    
    /**
     * Get performance trends over time.
     */
    private function getPerformanceTrends($period, $classId): array
    {
        $days = $period === 'weekly' ? 7 : 30;
        
        return collect(range(0, $days - 1))->map(function ($daysAgo) use ($classId) {
            $date = now()->subDays($daysAgo)->toDateString();
            
            $query = Submission::query()
                ->whereDate('created_at', $date)
                ->whereNotNull('overall_score');
            
            if ($classId) {
                $query->whereHas('assignment', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            }
            
            return [
                'date' => $date,
                'submissions' => $query->count(),
                'avg_score' => round($query->avg('overall_score') ?? 0, 1),
            ];
        })->reverse()->values();
    }
    
    /**
     * Get top performers analysis.
     */
    private function getTopPerformersAnalysis($period, $classId): array
    {
        $query = User::query()
            ->join('submissions', 'users.id', '=', 'submissions.user_id')
            ->whereNotNull('submissions.overall_score');
        
        if ($dateFilter = $this->buildDateFilter($period)) {
            $query->where('submissions.created_at', '>=', $dateFilter);
        }
        
        if ($classId) {
            $query->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                  ->where('assignments.class_id', $classId);
        }
        
        return $query->select([
                'users.id',
                'users.name',
                DB::raw('AVG(submissions.overall_score) as avg_score'),
                DB::raw('COUNT(submissions.id) as submission_count')
            ])
            ->groupBy('users.id', 'users.name')
            ->having('submission_count', '>=', 3) // At least 3 submissions
            ->orderByDesc('avg_score')
            ->limit(10)
            ->get()
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $user->name,
                    'avg_score' => round($user->avg_score, 1),
                    'submission_count' => $user->submission_count,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get engagement metrics.
     */
    private function getEngagementMetrics($period, $classId): array
    {
        $dateFilter = $this->buildDateFilter($period);
        
        $query = User::query()->role('student');
        
        if ($classId) {
            $query->whereHas('classes', function ($q) use ($classId) {
                $q->where('classes.id', $classId);
            });
        }
        
        $totalStudents = $query->count();
        
        $activeStudents = (clone $query)
            ->whereHas('submissions', function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('created_at', '>=', $dateFilter);
                }
            })
            ->count();
        
        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'engagement_rate' => $totalStudents > 0 
                ? round(($activeStudents / $totalStudents) * 100, 1)
                : 0,
        ];
    }
    
    /**
     * Build date filter based on period.
     */
    private function buildDateFilter($period): ?\Carbon\Carbon
    {
        switch ($period) {
            case 'weekly':
                return now()->startOfWeek();
            case 'monthly':
                return now()->startOfMonth();
            default:
                return null;
        }
    }
    
    /**
     * Get leaderboard snapshots with optional filtering.
     * @param Request $request HTTP request with optional period filter
     * @return \Illuminate\Http\JsonResponse Snapshot data
     */
    public function snapshots(Request $request)
    {
        $period = $request->get('period', 'weekly');
        
        // For now, return live data since we don't have LeaderboardSnapshot model
        // This can be enhanced later with actual snapshot storage
        $leaderboardData = $this->buildLeaderboardSnapshot($period);

        return response()->json([
            'period' => $period,
            'data' => $leaderboardData,
            'generated_at' => now()->toISOString(),
            'scope' => 'global'
        ]);
    }

    /**
     * regenerate rebuilds snapshot for a given period and returns it.
     * @param Request $request HTTP request with period parameter
     * @return \Illuminate\Http\JsonResponse Updated snapshot data
     */
    public function regenerate(Request $request)
    {
        $data = $request->validate([
            'period' => 'required|in:weekly,monthly'
        ]);
        
        $period = $data['period'];

        // Build fresh leaderboard data
        $leaderboardData = $this->buildLeaderboardSnapshot($period);
        
        // Log audit trail
        if (function_exists('audit')) {
            audit('leaderboard.regenerate', [
                'period' => $period,
                'user_count' => count($leaderboardData),
                'user_id' => auth()->id()
            ]);
        }

        return response()->json([
            'ok' => true,
            'period' => $period,
            'data' => $leaderboardData,
            'generated_at' => now()->toISOString(),
            'user_count' => count($leaderboardData)
        ]);
    }

    /**
     * Build leaderboard snapshot data for the specified period.
     * @param string $period The period (weekly/monthly)
     * @return array Leaderboard data with user rankings
     */
    private function buildLeaderboardSnapshot(string $period): array
    {
        // Calculate date range based on period
        $startDate = match ($period) {
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfWeek()
        };

        // Build leaderboard query
        $leaderboard = Submission::query()
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->whereNotNull('submissions.overall_score')
            ->where('submissions.created_at', '>=', $startDate)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(submissions.id) as total_submissions'),
                DB::raw('AVG(submissions.overall_score) as avg_score'),
                DB::raw('MAX(submissions.overall_score) as best_score'),
                DB::raw('SUM(submissions.overall_score) as total_points')
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('avg_score')
            ->orderByDesc('total_submissions')
            ->limit(50)
            ->get()
            ->map(function ($user, $index) use ($period) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'points' => (int) round($user->total_points),
                    'activity_count' => (int) $user->total_submissions,
                    'avg_score' => round($user->avg_score, 1),
                    'best_score' => round($user->best_score, 1),
                    'period' => $period
                ];
            })
            ->toArray();

        return $leaderboard;
    }

    /**
     * Determine user activity status.
     */
    private function getUserActivityStatus($lastSubmission): string
    {
        if (!$lastSubmission) {
            return 'inactive';
        }
        
        $lastSubmissionDate = \Carbon\Carbon::parse($lastSubmission);
        
        if ($lastSubmissionDate >= now()->subDays(7)) {
            return 'active';
        }
        
        if ($lastSubmissionDate >= now()->subDays(30)) {
            return 'moderate';
        }
        
        return 'inactive';
    }
}