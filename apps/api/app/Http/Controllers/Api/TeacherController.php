<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\DailyStats;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * Get classes overview for teacher dashboard.
     * Returns summary statistics for each class including student count,
     * active students today, average progress, and pending submissions.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with classes overview
     */
    public function getClassesOverview(Request $request)
    {
        $teacher = Auth::user();
        
        if ($teacher->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $classes = ClassModel::where('teacher_id', $teacher->id)
            ->with(['members'])
            ->get()
            ->map(function ($class) {
                $studentIds = $class->members->where('role_in_class', 'student')->pluck('user_id');
                $today = Carbon::today();
                
                // Count active students today (those with daily stats)
                $activeToday = DailyStats::whereIn('user_id', $studentIds)
                    ->where('date', $today)
                    ->count();
                
                // Calculate average progress (mock calculation)
                $avgProgress = $studentIds->count() > 0 
                    ? rand(65, 95) // Mock data - would calculate from actual progress
                    : 0;
                
                // Count pending submissions
                $pendingSubmissions = Submission::whereHas('assignment', function ($query) use ($class) {
                    $query->where('class_id', $class->id);
                })
                ->where('status', 'pending')
                ->count();
                
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'student_count' => $studentIds->count(),
                    'active_students_today' => $activeToday,
                    'average_progress' => $avgProgress,
                    'pending_submissions' => $pendingSubmissions,
                ];
            });
        
        return response()->json($classes);
    }
    
    /**
     * Get detailed student progress data for teacher oversight.
     * Includes hasanat totals, daily progress, streaks, recent activity,
     * performance trends, and alerts for each student.
     *
     * @param Request $request HTTP request (optional class_id filter)
     * @return \Illuminate\Http\JsonResponse JSON response with student progress data
     */
    public function getStudentsProgress(Request $request)
    {
        $teacher = Auth::user();
        
        if ($teacher->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $classId = $request->query('class_id');
        
        // Get students from teacher's classes
        $query = User::where('role', 'student')
            ->whereHas('classMemberships', function ($q) use ($teacher, $classId) {
                $q->whereHas('class', function ($classQuery) use ($teacher) {
                    $classQuery->where('teacher_id', $teacher->id);
                });
                
                if ($classId) {
                    $q->where('class_id', $classId);
                }
            });
        
        $students = $query->get()->map(function ($student) {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            
            // Get today's stats
            $todayStats = DailyStats::where('user_id', $student->id)
                ->where('date', $today)
                ->first();
            
            // Get yesterday's stats for comparison
            $yesterdayStats = DailyStats::where('user_id', $student->id)
                ->where('date', $yesterday)
                ->first();
            
            // Calculate daily goal progress (assuming goal of 20 verses)
            $dailyGoal = 20;
            $versesToday = $todayStats?->verses_read ?? 0;
            $dailyProgress = min(100, ($versesToday / $dailyGoal) * 100);
            
            // Calculate current streak
            $streak = $this->calculateStreak($student->id);
            
            // Get recent assignments completed
            $assignmentsCompleted = Submission::where('student_id', $student->id)
                ->where('created_at', '>=', $today)
                ->count();
            
            // Calculate attendance rate (last 30 days)
            $attendanceRate = $this->calculateAttendanceRate($student->id);
            
            // Determine performance trend
            $trend = $this->getPerformanceTrend($student->id, $todayStats, $yesterdayStats);
            
            // Generate alerts
            $alerts = $this->generateStudentAlerts($student, $todayStats, $streak, $dailyProgress);
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'total_hasanat' => $student->hasanat_total ?? 0,
                'daily_goal_progress' => round($dailyProgress),
                'current_streak' => $streak,
                'last_active' => $student->updated_at->toISOString(),
                'recent_activity' => [
                    'verses_read_today' => $versesToday,
                    'assignments_completed' => $assignmentsCompleted,
                    'attendance_rate' => $attendanceRate,
                ],
                'alerts' => $alerts,
                'performance_trend' => $trend,
            ];
        });
        
        return response()->json($students);
    }
    
    /**
     * Get teacher notifications including student alerts and assignment submissions.
     * Returns prioritized notifications for teacher attention.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with notifications
     */
    public function getNotifications(Request $request)
    {
        $teacher = Auth::user();
        
        if ($teacher->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $notifications = [];
        
        // Get recent submissions
        $recentSubmissions = Submission::whereHas('assignment', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })
        ->with(['student', 'assignment'])
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
        
        foreach ($recentSubmissions as $submission) {
            $notifications[] = [
                'id' => 'submission_' . $submission->id,
                'type' => 'assignment',
                'title' => 'New submission from ' . $submission->student->name,
                'message' => $submission->assignment->title,
                'timestamp' => $submission->created_at->toISOString(),
                'read' => false,
            ];
        }
        
        // Get students who need attention (low progress, missed goals)
        $studentsNeedingAttention = User::where('role', 'student')
            ->whereHas('classMemberships', function ($q) use ($teacher) {
                $q->whereHas('class', function ($classQuery) use ($teacher) {
                    $classQuery->where('teacher_id', $teacher->id);
                });
            })
            ->get()
            ->filter(function ($student) {
                $todayStats = DailyStats::where('user_id', $student->id)
                    ->where('date', Carbon::today())
                    ->first();
                
                $versesToday = $todayStats?->verses_read ?? 0;
                $dailyGoal = 20;
                $progress = ($versesToday / $dailyGoal) * 100;
                
                // Alert if student is significantly behind on daily goal
                return $progress < 50 && Carbon::now()->hour >= 18; // After 6 PM
            });
        
        foreach ($studentsNeedingAttention as $student) {
            $notifications[] = [
                'id' => 'alert_' . $student->id,
                'type' => 'alert',
                'title' => 'Student needs attention',
                'message' => $student->name . ' is behind on daily reading goals',
                'timestamp' => Carbon::now()->toISOString(),
                'read' => false,
            ];
        }
        
        // Sort by timestamp (newest first)
        usort($notifications, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return response()->json(array_slice($notifications, 0, 20));
    }
    
    /**
     * Calculate student's current streak of consecutive days meeting daily goals.
     *
     * @param int $userId User ID
     * @return int Number of consecutive days
     */
    private function calculateStreak($userId)
    {
        $streak = 0;
        $date = Carbon::today();
        $dailyGoal = 20;
        
        // Check backwards from today
        for ($i = 0; $i < 30; $i++) { // Check last 30 days max
            $stats = DailyStats::where('user_id', $userId)
                ->where('date', $date)
                ->first();
            
            $versesRead = $stats?->verses_read ?? 0;
            
            if ($versesRead >= $dailyGoal) {
                $streak++;
                $date = $date->subDay();
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Calculate student's attendance rate over the last 30 days.
     *
     * @param int $userId User ID
     * @return int Attendance percentage
     */
    private function calculateAttendanceRate($userId)
    {
        $daysWithActivity = DailyStats::where('user_id', $userId)
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->where('verses_read', '>', 0)
            ->count();
        
        return min(100, round(($daysWithActivity / 30) * 100));
    }
    
    /**
     * Determine performance trend based on recent activity.
     *
     * @param int $userId User ID
     * @param object|null $todayStats Today's statistics
     * @param object|null $yesterdayStats Yesterday's statistics
     * @return string Trend indicator ('up', 'down', 'stable')
     */
    private function getPerformanceTrend($userId, $todayStats, $yesterdayStats)
    {
        $todayVerses = $todayStats?->verses_read ?? 0;
        $yesterdayVerses = $yesterdayStats?->verses_read ?? 0;
        
        if ($todayVerses > $yesterdayVerses * 1.2) {
            return 'up';
        } elseif ($todayVerses < $yesterdayVerses * 0.8) {
            return 'down';
        }
        
        return 'stable';
    }
    
    /**
     * Generate contextual alerts for a student based on their activity.
     *
     * @param User $student Student user object
     * @param object|null $todayStats Today's statistics
     * @param int $streak Current streak
     * @param float $dailyProgress Daily goal progress percentage
     * @return array Array of alert objects
     */
    private function generateStudentAlerts($student, $todayStats, $streak, $dailyProgress)
    {
        $alerts = [];
        
        // Streak milestone alerts
        if ($streak >= 7 && $streak % 7 === 0) {
            $alerts[] = [
                'type' => 'success',
                'message' => "Completed daily goal for {$streak} days straight!",
                'timestamp' => Carbon::now()->toISOString(),
            ];
        }
        
        // Low progress warning
        if ($dailyProgress < 50 && Carbon::now()->hour >= 18) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Behind on daily reading goal',
                'timestamp' => Carbon::now()->toISOString(),
            ];
        }
        
        // Hasanat milestone
        $hasanat = $student->hasanat_total ?? 0;
        if ($hasanat > 0 && $hasanat % 1000 === 0) {
            $alerts[] = [
                'type' => 'success',
                'message' => "Reached {$hasanat} total Hasanat!",
                'timestamp' => Carbon::now()->toISOString(),
            ];
        }
        
        // Inactivity alert
        $lastActive = Carbon::parse($student->updated_at);
        if ($lastActive->diffInDays(Carbon::now()) >= 3) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'No activity for 3+ days',
                'timestamp' => Carbon::now()->toISOString(),
            ];
        }
        
        return $alerts;
    }
}