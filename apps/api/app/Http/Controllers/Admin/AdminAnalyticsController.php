<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * AdminAnalyticsController provides comprehensive system analytics and reporting.
 * Offers insights into user engagement, learning progress, and platform usage.
 */
class AdminAnalyticsController extends Controller
{
    /**
     * Provide consolidated analytics data for the admin dashboard.
     * Matches the structure expected by the web admin interface.
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'daily');
        $scope = $request->get('scope', 'global');

        $periodDays = match ($period) {
            'weekly' => 7,
            'monthly' => 30,
            default => 1,
        };

        $endDate = now();
        $startDate = (clone $endDate)->subDays($periodDays - 1)->startOfDay();

        $totalUsers = DB::table('users')->count();
        $activeUsers = DB::table('users')->where('status', 'active')->count();
        $newUsers = DB::table('users')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalClasses = DB::table('classes')->count();
        $totalAssignments = DB::table('assignments')->count();
        $totalSubmissions = DB::table('submissions')->count();

        $usersByRole = DB::table('users')
            ->select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        $usersByLevel = [
            'beginner' => DB::table('users')->where('hasanat_total', '<', 1000)->count(),
            'intermediate' => DB::table('users')->whereBetween('hasanat_total', [1000, 4999])->count(),
            'advanced' => DB::table('users')->where('hasanat_total', '>=', 5000)->count(),
        ];

        $activeInPeriod = DB::table('users')
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '>=', $startDate)
            ->count();

        $classesByLevel = DB::table('classes')
            ->select(
                DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings, '$.level')), 'general') as level"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        if (empty($classesByLevel) && $totalClasses > 0) {
            $classesByLevel = ['general' => $totalClasses];
        }

        $publishedAssignments = DB::table('assignments')->where('status', 'published')->count();
        $draftAssignments = DB::table('assignments')->where('status', 'draft')->count();
        $newAssignments = DB::table('assignments')
            ->where('created_at', '>=', $startDate)
            ->count();

        $pendingSubmissions = DB::table('submissions')->where('status', 'pending')->count();
        $gradedSubmissions = DB::table('submissions')->where('status', 'graded')->count();
        $newSubmissions = DB::table('submissions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $averageScore = DB::table('submissions')->whereNotNull('score')->avg('score') ?? 0;
        $submissionsInPeriod = $newSubmissions;

        $classMembers = DB::table('class_members')->count();

        $totalProgressRecords = DB::table('quran_progress')->count();
        $totalHasanat = DB::table('quran_progress')->sum('hasanat');
        $averageMemorization = DB::table('quran_progress')->avg('memorized_confidence') ?? 0;
        $activeReaders = DB::table('quran_progress')
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        $engagement = [
            'daily_active_users' => $activeInPeriod,
            'submission_rate' => $totalAssignments > 0
                ? round(($submissionsInPeriod / $totalAssignments) * 100, 2)
                : 0,
            'class_participation' => $totalUsers > 0
                ? round(min(100, ($classMembers / $totalUsers) * 100), 2)
                : 0,
        ];

        return response()->json([
            'overview' => [
                'total_users' => $totalUsers,
                'active_users' => max($activeUsers, $activeInPeriod),
                'new_users' => $newUsers,
                'total_classes' => $totalClasses,
                'total_assignments' => $totalAssignments,
                'total_submissions' => $totalSubmissions,
            ],
            'users' => [
                'by_role' => $usersByRole,
                'by_level' => $usersByLevel,
                'active_in_period' => $activeInPeriod,
            ],
            'classes' => [
                'total_classes' => $totalClasses,
                'classes_by_level' => $classesByLevel,
                'new_classes' => DB::table('classes')->where('created_at', '>=', $startDate)->count(),
            ],
            'assignments' => [
                'total_assignments' => $totalAssignments,
                'published_assignments' => $publishedAssignments,
                'draft_assignments' => $draftAssignments,
                'new_assignments' => $newAssignments,
            ],
            'submissions' => [
                'total_submissions' => $totalSubmissions,
                'pending_submissions' => $pendingSubmissions,
                'graded_submissions' => $gradedSubmissions,
                'new_submissions' => $newSubmissions,
                'average_score' => (float) $averageScore,
            ],
            'quran_progress' => [
                'total_progress_records' => $totalProgressRecords,
                'total_hasanat' => $totalHasanat,
                'average_memorization_confidence' => min(1, max(0, (float) $averageMemorization)),
                'active_readers' => $activeReaders,
            ],
            'engagement' => $engagement,
            'generated_at' => now()->toISOString(),
            'period' => $period,
            'scope' => $scope,
        ]);
    }

    /**
     * Get comprehensive platform analytics overview.
     * Provides high-level metrics and trends across all system areas.
     *
     * @param Request $request HTTP request with date filters
     * @return \Illuminate\Http\JsonResponse comprehensive analytics overview
     */
    public function overview(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        // User growth metrics
        $userMetrics = $this->getUserGrowthMetrics($startDate, $endDate);
        
        // Learning activity metrics
        $learningMetrics = $this->getLearningActivityMetrics($startDate, $endDate);
        
        // Revenue metrics
        $revenueMetrics = $this->getRevenueMetrics($startDate, $endDate);
        
        // Engagement metrics
        $engagementMetrics = $this->getEngagementMetrics($startDate, $endDate);
        
        // Performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($startDate, $endDate);
        
        return response()->json([
            'user_growth' => $userMetrics,
            'learning_activity' => $learningMetrics,
            'revenue' => $revenueMetrics,
            'engagement' => $engagementMetrics,
            'performance' => $performanceMetrics,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Get detailed user analytics and demographics.
     * Provides insights into user behavior and platform adoption.
     *
     * @param Request $request HTTP request with filters
     * @return \Illuminate\Http\JsonResponse user analytics data
     */
    public function users(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        // User registration trends
        $registrationTrends = $this->getUserRegistrationTrends($startDate, $endDate);
        
        // User role distribution
        $roleDistribution = DB::table('users')
            ->select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->role => $item->count];
            });
        
        // Active users by period
        $activeUsers = [
            'daily' => $this->getActiveUsersByPeriod('day', 1),
            'weekly' => $this->getActiveUsersByPeriod('week', 7),
            'monthly' => $this->getActiveUsersByPeriod('month', 30),
        ];
        
        // User retention analysis
        $retentionAnalysis = $this->getUserRetentionAnalysis();
        
        // Geographic distribution (if available)
        $geographicData = $this->getGeographicDistribution();
        
        return response()->json([
            'registration_trends' => $registrationTrends,
            'role_distribution' => $roleDistribution,
            'active_users' => $activeUsers,
            'retention_analysis' => $retentionAnalysis,
            'geographic_distribution' => $geographicData,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    /**
     * Get learning progress and educational analytics.
     * Provides insights into student performance and learning outcomes.
     *
     * @param Request $request HTTP request with filters
     * @return \Illuminate\Http\JsonResponse learning analytics data
     */
    public function learning(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        // Assignment completion trends
        $completionTrends = $this->getAssignmentCompletionTrends($startDate, $endDate);
        
        // Performance distribution
        $performanceDistribution = $this->getPerformanceDistribution($startDate, $endDate);
        
        // Learning path analysis
        $learningPaths = $this->getLearningPathAnalysis();
        
        // Difficulty analysis
        $difficultyAnalysis = $this->getDifficultyAnalysis($startDate, $endDate);
        
        // Progress tracking
        $progressTracking = $this->getProgressTracking($startDate, $endDate);
        
        return response()->json([
            'completion_trends' => $completionTrends,
            'performance_distribution' => $performanceDistribution,
            'learning_paths' => $learningPaths,
            'difficulty_analysis' => $difficultyAnalysis,
            'progress_tracking' => $progressTracking,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    /**
     * Get system usage and technical analytics.
     * Provides insights into platform performance and technical metrics.
     *
     * @param Request $request HTTP request with filters
     * @return \Illuminate\Http\JsonResponse system analytics data
     */
    public function system(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        // API usage metrics
        $apiMetrics = $this->getApiUsageMetrics($startDate, $endDate);
        
        // File upload analytics
        $uploadMetrics = $this->getUploadMetrics($startDate, $endDate);
        
        // Whisper job analytics
        $whisperMetrics = $this->getWhisperJobMetrics($startDate, $endDate);
        
        // Error tracking
        $errorMetrics = $this->getErrorMetrics($startDate, $endDate);
        
        // Storage usage
        $storageMetrics = $this->getStorageMetrics();
        
        return response()->json([
            'api_usage' => $apiMetrics,
            'upload_metrics' => $uploadMetrics,
            'whisper_jobs' => $whisperMetrics,
            'error_tracking' => $errorMetrics,
            'storage_usage' => $storageMetrics,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    /**
     * Get user growth metrics for the specified period.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array user growth data
     */
    private function getUserGrowthMetrics(string $startDate, string $endDate): array
    {
        $totalUsers = DB::table('users')->count();
        $newUsers = DB::table('users')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $previousPeriodStart = Carbon::parse($startDate)
            ->subDays(Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate)))
            ->toDateString();
        
        $previousNewUsers = DB::table('users')
            ->whereBetween('created_at', [$previousPeriodStart, $startDate])
            ->count();
        
        $growthRate = $previousNewUsers > 0 
            ? round((($newUsers - $previousNewUsers) / $previousNewUsers) * 100, 1)
            : 0;
        
        return [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'growth_rate' => $growthRate,
            'daily_average' => round($newUsers / max(1, Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate))), 1),
        ];
    }
    
    /**
     * Get learning activity metrics.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array learning activity data
     */
    private function getLearningActivityMetrics(string $startDate, string $endDate): array
    {
        $totalSubmissions = DB::table('submissions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $completedSubmissions = DB::table('submissions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
        
        $avgScore = DB::table('submissions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->avg('score');
        
        return [
            'total_submissions' => $totalSubmissions,
            'completed_submissions' => $completedSubmissions,
            'completion_rate' => $totalSubmissions > 0 ? round(($completedSubmissions / $totalSubmissions) * 100, 1) : 0,
            'average_score' => round($avgScore ?? 0, 1),
        ];
    }
    
    /**
     * Get revenue metrics.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array revenue data
     */
    private function getRevenueMetrics(string $startDate, string $endDate): array
    {
        $totalRevenue = DB::table('invoices')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');
        
        $totalInvoices = DB::table('invoices')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        return [
            'total_revenue' => $totalRevenue,
            'total_invoices' => $totalInvoices,
            'average_invoice_value' => $totalInvoices > 0 ? round($totalRevenue / $totalInvoices, 2) : 0,
        ];
    }
    
    /**
     * Get engagement metrics.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array engagement data
     */
    private function getEngagementMetrics(string $startDate, string $endDate): array
    {
        // This would typically track user sessions, page views, etc.
        // For now, we'll use submission activity as a proxy for engagement
        $activeUsers = DB::table('submissions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count();
        
        $totalUsers = DB::table('users')->count();
        
        return [
            'active_users' => $activeUsers,
            'engagement_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
        ];
    }
    
    /**
     * Get performance metrics.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array performance data
     */
    private function getPerformanceMetrics(string $startDate, string $endDate): array
    {
        $whisperJobs = DB::table('whisper_jobs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $successfulJobs = DB::table('whisper_jobs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
        
        return [
            'whisper_jobs_processed' => $whisperJobs,
            'whisper_success_rate' => $whisperJobs > 0 ? round(($successfulJobs / $whisperJobs) * 100, 1) : 0,
        ];
    }
    
    /**
     * Get user registration trends.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array registration trends
     */
    private function getUserRegistrationTrends(string $startDate, string $endDate): array
    {
        return collect()
            ->range(0, Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate)))
            ->map(function ($daysFromStart) use ($startDate) {
                $date = Carbon::parse($startDate)->addDays($daysFromStart)->toDateString();
                
                $registrations = DB::table('users')
                    ->whereDate('created_at', $date)
                    ->count();
                
                return [
                    'date' => $date,
                    'registrations' => $registrations,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get active users by period.
     * @param string $period Period type
     * @param int $days Number of days
     * @return int active user count
     */
    private function getActiveUsersByPeriod(string $period, int $days): int
    {
        return DB::table('submissions')
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct('user_id')
            ->count();
    }
    
    /**
     * Get user retention analysis.
     * @return array retention data
     */
    private function getUserRetentionAnalysis(): array
    {
        // Simplified retention analysis
        $weeklyRetention = DB::table('users')
            ->where('created_at', '>=', now()->subWeeks(2))
            ->where('created_at', '<=', now()->subWeek())
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('submissions')
                    ->whereColumn('submissions.user_id', 'users.id')
                    ->where('submissions.created_at', '>=', now()->subWeek());
            })
            ->count();
        
        $totalNewUsers = DB::table('users')
            ->where('created_at', '>=', now()->subWeeks(2))
            ->where('created_at', '<=', now()->subWeek())
            ->count();
        
        return [
            'weekly_retention_rate' => $totalNewUsers > 0 ? round(($weeklyRetention / $totalNewUsers) * 100, 1) : 0,
        ];
    }
    
    /**
     * Get geographic distribution (placeholder).
     * @return array geographic data
     */
    private function getGeographicDistribution(): array
    {
        // This would require IP geolocation or user-provided location data
        return [
            'note' => 'Geographic data not available - requires IP geolocation implementation',
        ];
    }
    
    /**
     * Get assignment completion trends.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array completion trends
     */
    private function getAssignmentCompletionTrends(string $startDate, string $endDate): array
    {
        return collect()
            ->range(0, Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate)))
            ->map(function ($daysFromStart) use ($startDate) {
                $date = Carbon::parse($startDate)->addDays($daysFromStart)->toDateString();
                
                $completions = DB::table('submissions')
                    ->whereDate('created_at', $date)
                    ->where('status', 'completed')
                    ->count();
                
                return [
                    'date' => $date,
                    'completions' => $completions,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get performance distribution.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array performance distribution
     */
    private function getPerformanceDistribution(string $startDate, string $endDate): array
    {
        $scoreRanges = [
            '0-20' => [0, 20],
            '21-40' => [21, 40],
            '41-60' => [41, 60],
            '61-80' => [61, 80],
            '81-100' => [81, 100],
        ];
        
        $distribution = [];
        
        foreach ($scoreRanges as $range => $bounds) {
            $count = DB::table('submissions')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->whereBetween('score', $bounds)
                ->count();
            
            $distribution[$range] = $count;
        }
        
        return $distribution;
    }
    
    /**
     * Placeholder methods for additional analytics.
     */
    private function getLearningPathAnalysis(): array { return ['note' => 'Learning path analysis not implemented']; }
    private function getDifficultyAnalysis(string $startDate, string $endDate): array { return ['note' => 'Difficulty analysis not implemented']; }
    private function getProgressTracking(string $startDate, string $endDate): array { return ['note' => 'Progress tracking not implemented']; }
    private function getApiUsageMetrics(string $startDate, string $endDate): array { return ['note' => 'API usage metrics require request logging']; }
    private function getUploadMetrics(string $startDate, string $endDate): array { return ['note' => 'Upload metrics not implemented']; }
    private function getWhisperJobMetrics(string $startDate, string $endDate): array { return ['note' => 'Whisper job metrics available in AdminWhisperController']; }
    private function getErrorMetrics(string $startDate, string $endDate): array { return ['note' => 'Error metrics require error logging implementation']; }
    private function getStorageMetrics(): array { return ['note' => 'Storage metrics require S3 integration']; }
}