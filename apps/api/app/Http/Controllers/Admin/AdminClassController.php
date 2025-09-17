<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AdminClassController manages class oversight and analytics.
 * Provides class listing, enrollment management, and performance metrics.
 */
class AdminClassController extends Controller
{
    /**
     * Get paginated list of classes with enrollment and activity metrics.
     * Includes teacher info, student count, and recent activity.
     *
     * @param Request $request HTTP request with optional filters
     * @return \Illuminate\Http\JsonResponse paginated class list with metrics
     */
    public function index(Request $request)
    {
        $query = ClassModel::query()
            ->with(['teacher:id,name,email'])
            ->withCount(['students', 'assignments'])
            ->select(['id', 'name', 'description', 'level', 'teacher_id', 'created_at', 'updated_at']);
        
        // Search by class name or teacher name
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('teacher', function ($teacherQuery) use ($search) {
                      $teacherQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by level
        if ($level = $request->get('level')) {
            $query->where('level', $level);
        }
        
        // Filter by teacher
        if ($teacherId = $request->get('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $classes = $query->paginate($request->get('per_page', 20));
        
        // Transform data to include additional metrics
        $classes->getCollection()->transform(function ($class) {
            // Get recent submission activity for this class
            $recentSubmissions = DB::table('submissions')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->where('assignments.class_id', $class->id)
                ->where('submissions.created_at', '>=', now()->subDays(7))
                ->count();
            
            // Get active students (submitted in last 30 days)
            $activeStudents = DB::table('submissions')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->where('assignments.class_id', $class->id)
                ->where('submissions.created_at', '>=', now()->subDays(30))
                ->distinct('submissions.user_id')
                ->count('submissions.user_id');
            
            return [
                'id' => $class->id,
                'name' => $class->name,
                'description' => $class->description,
                'level' => $class->level,
                'teacher' => [
                    'id' => $class->teacher->id,
                    'name' => $class->teacher->name,
                    'email' => $class->teacher->email,
                ],
                'metrics' => [
                    'total_students' => $class->students_count,
                    'active_students' => $activeStudents,
                    'total_assignments' => $class->assignments_count,
                    'recent_submissions' => $recentSubmissions,
                    'engagement_rate' => $class->students_count > 0 
                        ? round(($activeStudents / $class->students_count) * 100, 1)
                        : 0,
                ],
                'created_at' => $class->created_at->toISOString(),
                'updated_at' => $class->updated_at->toISOString(),
            ];
        });
        
        return response()->json($classes);
    }
    
    /**
     * Get detailed analytics for a specific class.
     * Includes student performance, assignment completion rates, and trends.
     *
     * @param int $id Class ID
     * @return \Illuminate\Http\JsonResponse detailed class analytics
     */
    public function analytics($id)
    {
        $class = ClassModel::with(['teacher:id,name', 'students:id,name,email'])
            ->findOrFail($id);
        
        // Assignment completion rates
        $assignmentStats = DB::table('assignments')
            ->leftJoin('submissions', 'assignments.id', '=', 'submissions.assignment_id')
            ->where('assignments.class_id', $id)
            ->select(
                'assignments.id',
                'assignments.title',
                'assignments.created_at',
                DB::raw('COUNT(DISTINCT submissions.user_id) as completed_by'),
                DB::raw('(SELECT COUNT(*) FROM class_user WHERE class_id = ?) as total_students')
            )
            ->groupBy('assignments.id', 'assignments.title', 'assignments.created_at')
            ->setBindings([$id])
            ->get()
            ->map(function ($assignment) {
                $completionRate = $assignment->total_students > 0 
                    ? round(($assignment->completed_by / $assignment->total_students) * 100, 1)
                    : 0;
                
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'completed_by' => $assignment->completed_by,
                    'total_students' => $assignment->total_students,
                    'completion_rate' => $completionRate,
                    'created_at' => $assignment->created_at,
                ];
            });
        
        // Student performance overview
        $studentPerformance = DB::table('class_user')
            ->join('users', 'class_user.user_id', '=', 'users.id')
            ->leftJoin('submissions', function ($join) {
                $join->on('users.id', '=', 'submissions.user_id')
                     ->whereExists(function ($query) {
                         $query->select(DB::raw(1))
                               ->from('assignments')
                               ->whereRaw('assignments.id = submissions.assignment_id')
                               ->whereRaw('assignments.class_id = class_user.class_id');
                     });
            })
            ->where('class_user.class_id', $id)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(DISTINCT submissions.id) as total_submissions'),
                DB::raw('AVG(CASE WHEN submissions.overall_score IS NOT NULL THEN submissions.overall_score ELSE NULL END) as avg_score'),
                DB::raw('MAX(submissions.created_at) as last_submission')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'total_submissions' => $student->total_submissions,
                    'avg_score' => $student->avg_score ? round($student->avg_score, 1) : null,
                    'last_submission' => $student->last_submission,
                    'status' => $this->getStudentStatus($student->last_submission),
                ];
            });
        
        // Weekly activity trend (last 8 weeks)
        $weeklyActivity = collect(range(0, 7))->map(function ($weeksAgo) use ($id) {
            $startOfWeek = now()->subWeeks($weeksAgo)->startOfWeek();
            $endOfWeek = now()->subWeeks($weeksAgo)->endOfWeek();
            
            $submissions = DB::table('submissions')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->where('assignments.class_id', $id)
                ->whereBetween('submissions.created_at', [$startOfWeek, $endOfWeek])
                ->count();
            
            return [
                'week_start' => $startOfWeek->toDateString(),
                'submissions' => $submissions,
            ];
        })->reverse()->values();
        
        return response()->json([
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'description' => $class->description,
                'level' => $class->level,
                'teacher' => [
                    'id' => $class->teacher->id,
                    'name' => $class->teacher->name,
                ],
                'total_students' => $class->students->count(),
            ],
            'assignment_stats' => $assignmentStats,
            'student_performance' => $studentPerformance,
            'weekly_activity' => $weeklyActivity,
            'generated_at' => now()->toISOString(),
        ]);
    }
    
    /**
     * Determine student activity status.
     * @param string|null $lastSubmission Last submission timestamp
     * @return string active|inactive|new
     */
    private function getStudentStatus($lastSubmission): string
    {
        if (!$lastSubmission) {
            return 'new';
        }
        
        $lastSubmissionDate = \Carbon\Carbon::parse($lastSubmission);
        
        if ($lastSubmissionDate >= now()->subDays(7)) {
            return 'active';
        }
        
        if ($lastSubmissionDate >= now()->subDays(30)) {
            return 'inactive';
        }
        
        return 'dormant';
    }
}