<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherAnalyticsController extends Controller
{
    /**
     * Get comprehensive analytics data for teacher dashboard.
     * Includes performance trends, student metrics, and class insights.
     */
    public function index(Request $request)
    {
        $teacher = $request->user();
        $timeRange = $request->get('timeRange', 'month');
        $classId = $request->get('classId', 'all');
        
        // Calculate date range based on timeRange parameter
        $endDate = Carbon::now();
        $startDate = match($timeRange) {
            'week' => $endDate->copy()->subWeek(),
            'quarter' => $endDate->copy()->subMonths(3),
            default => $endDate->copy()->subMonth(),
        };
        
        // Get teacher's classes
        $classesQuery = $teacher->classes();
        if ($classId !== 'all') {
            $classesQuery->where('id', $classId);
        }
        $classes = $classesQuery->get();
        $classIds = $classes->pluck('id');
        
        // Get key metrics
        $metrics = $this->getKeyMetrics($classIds, $startDate, $endDate);
        
        // Get performance trends
        $performanceTrends = $this->getPerformanceTrends($classIds, $startDate, $endDate);
        
        // Get tajweed error analysis
        $tajweedErrors = $this->getTajweedErrors($classIds, $startDate, $endDate);
        
        // Get top students
        $topStudents = $this->getTopStudents($classIds, $startDate, $endDate);
        
        // Get class engagement metrics
        $engagement = $this->getEngagementMetrics($classIds, $startDate, $endDate);
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity($classIds, 10);
        
        return response()->json([
            'metrics' => $metrics,
            'performanceTrends' => $performanceTrends,
            'tajweedErrors' => $tajweedErrors,
            'topStudents' => $topStudents,
            'engagement' => $engagement,
            'recentActivity' => $recentActivity,
            'classes' => $classes->map(fn($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'level' => $class->level,
                'student_count' => $class->students()->count()
            ])
        ]);
    }
    
    /**
     * Calculate key metrics for the teacher dashboard.
     */
    private function getKeyMetrics($classIds, $startDate, $endDate)
    {
        // Average score across all submissions
        $avgScore = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->whereNotNull('submissions.score')
            ->avg('submissions.score');
            
        // Completion rate
        $totalAssignments = DB::table('assignments')
            ->whereIn('class_id', $classIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $completedSubmissions = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->where('submissions.status', 'completed')
            ->count();
            
        $completionRate = $totalAssignments > 0 ? ($completedSubmissions / $totalAssignments) * 100 : 0;
        
        // Active students count
        $activeStudents = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->distinct('submissions.user_id')
            ->count('submissions.user_id');
            
        // Hours taught (estimated from assignment duration)
        $hoursTaught = DB::table('assignments')
            ->whereIn('class_id', $classIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('estimated_duration') / 60; // Convert minutes to hours
        
        return [
            'averageScore' => round($avgScore ?? 0, 1),
            'completionRate' => round($completionRate, 1),
            'activeStudents' => $activeStudents,
            'hoursTaught' => round($hoursTaught ?? 0, 1)
        ];
    }
    
    /**
     * Get performance trends over time.
     */
    private function getPerformanceTrends($classIds, $startDate, $endDate)
    {
        $trends = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->whereNotNull('submissions.score')
            ->selectRaw('DATE(submissions.created_at) as date, AVG(submissions.score) as average, COUNT(*) as submissions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return $trends->map(function($trend) {
            return [
                'date' => Carbon::parse($trend->date)->format('M j'),
                'average' => round($trend->average, 1),
                'submissions' => $trend->submissions
            ];
        });
    }
    
    /**
     * Get tajweed error analysis.
     */
    private function getTajweedErrors($classIds, $startDate, $endDate)
    {
        // This would need to be implemented based on your feedback/error tracking system
        // For now, returning mock data structure that matches the frontend
        return [
            ['rule' => 'Noon Sakinah', 'errors' => 15, 'improvement' => '+5%'],
            ['rule' => 'Meem Sakinah', 'errors' => 8, 'improvement' => '+12%'],
            ['rule' => 'Qalqalah', 'errors' => 12, 'improvement' => '-3%'],
            ['rule' => 'Madd', 'errors' => 6, 'improvement' => '+8%'],
        ];
    }
    
    /**
     * Get top performing students.
     */
    private function getTopStudents($classIds, $startDate, $endDate)
    {
        $topStudents = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->whereNotNull('submissions.score')
            ->selectRaw('users.name, AVG(submissions.score) as average_score, COUNT(*) as submission_count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('average_score')
            ->limit(5)
            ->get();
            
        return $topStudents->map(function($student) {
            return [
                'name' => $student->name,
                'score' => round($student->average_score, 1),
                'improvement' => '+' . rand(3, 15) . '%' // Mock improvement data
            ];
        });
    }
    
    /**
     * Get class engagement metrics.
     */
    private function getEngagementMetrics($classIds, $startDate, $endDate)
    {
        $totalAssignments = DB::table('assignments')
            ->whereIn('class_id', $classIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $completedAssignments = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->where('submissions.status', 'completed')
            ->count();
            
        $onTimeSubmissions = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('assignments.class_id', $classIds)
            ->whereBetween('submissions.created_at', [$startDate, $endDate])
            ->whereRaw('submissions.created_at <= assignments.due_date')
            ->count();
            
        return [
            'assignmentCompletion' => $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 1) : 0,
            'onTimeSubmissions' => $completedAssignments > 0 ? round(($onTimeSubmissions / $completedAssignments) * 100, 1) : 0,
            'classParticipation' => rand(85, 95), // Mock data
            'feedbackResponse' => rand(70, 85) // Mock data
        ];
    }
    
    /**
     * Get recent activity for the teacher.
     */
    private function getRecentActivity($classIds, $limit = 10)
    {
        $activities = DB::table('submissions')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->whereIn('assignments.class_id', $classIds)
            ->select(
                'submissions.created_at',
                'assignments.title as activity',
                'users.name as student',
                'submissions.score',
                'submissions.status'
            )
            ->orderByDesc('submissions.created_at')
            ->limit($limit)
            ->get();
            
        return $activities->map(function($activity) {
            return [
                'date' => Carbon::parse($activity->created_at)->format('M j, Y'),
                'activity' => $activity->activity,
                'student' => $activity->student,
                'score' => $activity->score ? round($activity->score, 1) . '%' : 'N/A',
                'status' => ucfirst($activity->status)
            ];
        });
    }
}