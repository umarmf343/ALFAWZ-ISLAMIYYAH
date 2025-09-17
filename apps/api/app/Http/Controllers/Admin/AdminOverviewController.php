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
 * AdminOverviewController provides high-level KPIs and metrics for admin dashboard.
 * Shows active users, submissions, payments, and system health indicators.
 */
class AdminOverviewController extends Controller
{
    /**
     * Get overview metrics for admin dashboard.
     * Returns active students/teachers, paid users, today's submissions, and other KPIs.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with metrics
     */
    public function metrics(Request $request)
    {
        // Active students (logged in within last 30 days)
        $activeStudents = User::role('student')
            ->where('last_login_at', '>=', now()->subDays(30))
            ->count();
        
        // Active teachers (logged in within last 30 days)
        $activeTeachers = User::role('teacher')
            ->where('last_login_at', '>=', now()->subDays(30))
            ->count();
        
        // Paid users (users with successful invoices)
        $paidUsers = DB::table('invoices')
            ->where('status', 'paid')
            ->distinct('user_id')
            ->count('user_id');
        
        // Today's submissions
        $submissionsToday = Submission::whereDate('created_at', today())->count();
        
        // This week's submissions
        $submissionsThisWeek = Submission::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        // Total users by role
        $usersByRole = User::select('roles.name as role', DB::raw('count(*) as count'))
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->groupBy('roles.name')
            ->pluck('count', 'role')
            ->toArray();
        
        // Recent activity (last 10 submissions)
        $recentActivity = Submission::with(['user:id,name', 'assignment:id,title'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'user_name' => $submission->user->name ?? 'Unknown',
                    'assignment_title' => $submission->assignment->title ?? 'Unknown',
                    'status' => $submission->status,
                    'created_at' => $submission->created_at->toISOString(),
                ];
            });
        
        // System health indicators
        $systemHealth = [
            'database_connection' => $this->checkDatabaseConnection(),
            'storage_available' => $this->checkStorageHealth(),
            'queue_health' => $this->checkQueueHealth(),
        ];
        
        return response()->json([
            'active_students' => $activeStudents,
            'active_teachers' => $activeTeachers,
            'paid_users' => $paidUsers,
            'submissions_today' => $submissionsToday,
            'submissions_this_week' => $submissionsThisWeek,
            'users_by_role' => $usersByRole,
            'recent_activity' => $recentActivity,
            'system_health' => $systemHealth,
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Check database connection health.
     * @return bool true if database is accessible
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check storage system health.
     * @return bool true if storage is accessible
     */
    private function checkStorageHealth(): bool
    {
        try {
            // Simple storage check - attempt to get disk info
            $disk = \Storage::disk(config('filesystems.default'));
            return $disk !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check queue system health.
     * @return bool true if queue system is operational
     */
    private function checkQueueHealth(): bool
    {
        try {
            // For file-based queues, check if jobs table exists
            return DB::getSchemaBuilder()->hasTable('jobs');
        } catch (\Exception $e) {
            return false;
        }
    }
}