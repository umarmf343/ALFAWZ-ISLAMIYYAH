<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnalyticsSnapshot;
use App\Models\User;
use App\Models\Classes;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\QuranProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GenerateAnalyticsSnapshot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:generate-snapshot 
                            {--scope=global : Scope of analytics (global, class, user)}
                            {--period=daily : Period for analytics (daily, weekly, monthly)}
                            {--force : Force regeneration even if snapshot exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate analytics snapshots for admin dashboard';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $scope = $this->option('scope');
        $period = $this->option('period');
        $force = $this->option('force');

        $this->info("Generating analytics snapshot for scope: {$scope}, period: {$period}");

        try {
            // Check if snapshot already exists for today
            $today = Carbon::today();
            $existing = AnalyticsSnapshot::where('scope', $scope)
                ->where('period', $period)
                ->whereDate('created_at', $today)
                ->first();

            if ($existing && !$force) {
                $this->warn('Analytics snapshot already exists for today. Use --force to regenerate.');
                return Command::SUCCESS;
            }

            // Generate analytics data based on scope and period
            $analyticsData = $this->generateAnalyticsData($scope, $period);

            // Create or update snapshot
            if ($existing && $force) {
                $existing->update(['data_json' => $analyticsData]);
                $this->info('Analytics snapshot updated successfully.');
            } else {
                AnalyticsSnapshot::create([
                    'scope' => $scope,
                    'period' => $period,
                    'data_json' => $analyticsData,
                ]);
                $this->info('Analytics snapshot created successfully.');
            }

            // Clear related cache
            Cache::forget("analytics_snapshot_{$scope}_{$period}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate analytics snapshot: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Generate analytics data based on scope and period.
     *
     * @param string $scope
     * @param string $period
     * @return array
     */
    private function generateAnalyticsData(string $scope, string $period): array
    {
        $dateRange = $this->getDateRange($period);
        
        return [
            'overview' => $this->generateOverviewMetrics($dateRange),
            'users' => $this->generateUserMetrics($dateRange),
            'classes' => $this->generateClassMetrics($dateRange),
            'assignments' => $this->generateAssignmentMetrics($dateRange),
            'submissions' => $this->generateSubmissionMetrics($dateRange),
            'quran_progress' => $this->generateQuranProgressMetrics($dateRange),
            'engagement' => $this->generateEngagementMetrics($dateRange),
            'generated_at' => now()->toISOString(),
            'period' => $period,
            'scope' => $scope,
        ];
    }

    /**
     * Get date range based on period.
     *
     * @param string $period
     * @return array
     */
    private function getDateRange(string $period): array
    {
        $end = Carbon::now();
        
        switch ($period) {
            case 'daily':
                $start = $end->copy()->subDay();
                break;
            case 'weekly':
                $start = $end->copy()->subWeek();
                break;
            case 'monthly':
                $start = $end->copy()->subMonth();
                break;
            default:
                $start = $end->copy()->subDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Generate overview metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateOverviewMetrics(array $dateRange): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::whereBetween('last_login_at', [$dateRange['start'], $dateRange['end']])->count(),
            'new_users' => User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_classes' => Classes::count(),
            'total_assignments' => Assignment::count(),
            'total_submissions' => Submission::count(),
        ];
    }

    /**
     * Generate user metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateUserMetrics(array $dateRange): array
    {
        return [
            'by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray(),
            'by_level' => User::select('level', DB::raw('count(*) as count'))
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'active_in_period' => User::whereBetween('last_login_at', [$dateRange['start'], $dateRange['end']])->count(),
        ];
    }

    /**
     * Generate class metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateClassMetrics(array $dateRange): array
    {
        return [
            'total_classes' => Classes::count(),
            'classes_by_level' => Classes::select('level', DB::raw('count(*) as count'))
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'new_classes' => Classes::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
        ];
    }

    /**
     * Generate assignment metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateAssignmentMetrics(array $dateRange): array
    {
        return [
            'total_assignments' => Assignment::count(),
            'published_assignments' => Assignment::where('status', 'published')->count(),
            'draft_assignments' => Assignment::where('status', 'draft')->count(),
            'new_assignments' => Assignment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
        ];
    }

    /**
     * Generate submission metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateSubmissionMetrics(array $dateRange): array
    {
        return [
            'total_submissions' => Submission::count(),
            'pending_submissions' => Submission::where('status', 'pending')->count(),
            'graded_submissions' => Submission::where('status', 'graded')->count(),
            'new_submissions' => Submission::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'average_score' => Submission::whereNotNull('score')->avg('score'),
        ];
    }

    /**
     * Generate Quran progress metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateQuranProgressMetrics(array $dateRange): array
    {
        return [
            'total_progress_records' => QuranProgress::count(),
            'total_hasanat' => QuranProgress::sum('hasanat'),
            'average_memorization_confidence' => QuranProgress::avg('memorized_confidence'),
            'active_readers' => QuranProgress::whereBetween('last_seen_at', [$dateRange['start'], $dateRange['end']])
                ->distinct('user_id')
                ->count(),
        ];
    }

    /**
     * Generate engagement metrics.
     *
     * @param array $dateRange
     * @return array
     */
    private function generateEngagementMetrics(array $dateRange): array
    {
        return [
            'daily_active_users' => User::whereBetween('last_login_at', [$dateRange['start'], $dateRange['end']])->count(),
            'submission_rate' => $this->calculateSubmissionRate($dateRange),
            'class_participation' => $this->calculateClassParticipation($dateRange),
        ];
    }

    /**
     * Calculate submission rate.
     *
     * @param array $dateRange
     * @return float
     */
    private function calculateSubmissionRate(array $dateRange): float
    {
        $totalAssignments = Assignment::where('status', 'published')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        if ($totalAssignments === 0) {
            return 0.0;
        }

        $totalSubmissions = Submission::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return round(($totalSubmissions / $totalAssignments) * 100, 2);
    }

    /**
     * Calculate class participation rate.
     *
     * @param array $dateRange
     * @return float
     */
    private function calculateClassParticipation(array $dateRange): float
    {
        $totalStudents = User::where('role', 'student')->count();
        
        if ($totalStudents === 0) {
            return 0.0;
        }

        $activeStudents = User::where('role', 'student')
            ->whereBetween('last_login_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return round(($activeStudents / $totalStudents) * 100, 2);
    }
}