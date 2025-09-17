<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MemorizationPlan;
use App\Models\SrsQueue;
use App\Models\QuranProgress;
use App\Notifications\MemorizationProgressUpdated;
use App\Services\WhisperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MemorizationController extends Controller
{
    protected $whisperService;

    public function __construct(WhisperService $whisperService)
    {
        $this->whisperService = $whisperService;
    }

    /**
     * Get student's memorization plans.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with plans
     */
    public function getPlans(Request $request)
    {
        $student = Auth::user();
        
        $plans = MemorizationPlan::where('student_id', $student->id)
            ->with(['srsQueues' => function($query) {
                $query->where('due_at', '<=', now())
                      ->orderBy('due_at', 'asc');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Create a new memorization plan.
     *
     * @param Request $request HTTP request with plan data
     * @return \Illuminate\Http\JsonResponse JSON response with created plan
     */
    public function createPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'surah_id' => 'required|integer|min:1|max:114',
            'start_ayah' => 'required|integer|min:1',
            'end_ayah' => 'required|integer|min:1',
            'daily_target' => 'required|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Auth::user();
        
        $plan = MemorizationPlan::create([
            'student_id' => $student->id,
            'title' => $request->title,
            'surah_id' => $request->surah_id,
            'start_ayah' => $request->start_ayah,
            'end_ayah' => $request->end_ayah,
            'daily_target' => $request->daily_target,
            'status' => 'active',
            'created_at' => now()
        ]);

        // Create initial SRS queue entries
        $this->createInitialSrsEntries($plan);

        return response()->json([
            'success' => true,
            'data' => $plan->load('srsQueues')
        ], 201);
    }

    /**
     * Get due reviews for the student.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with due reviews
     */
    public function getDueReviews(Request $request)
    {
        $student = Auth::user();
        
        $dueReviews = SrsQueue::where('student_id', $student->id)
            ->where('due_at', '<=', now())
            ->with('memorizationPlan')
            ->orderBy('due_at', 'asc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dueReviews
        ]);
    }

    /**
     * Submit a memorization review with audio analysis.
     *
     * @param Request $request HTTP request with review data and audio
     * @return \Illuminate\Http\JsonResponse JSON response with analysis results
     */
    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:memorization_plans,id',
            'surah_id' => 'required|integer|min:1|max:114',
            'ayah_id' => 'required|integer|min:1',
            'confidence_score' => 'required|numeric|min:0|max:1',
            'time_spent' => 'required|integer|min:1',
            'audio_file' => 'nullable|file|mimes:wav,mp3,m4a|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Auth::user();
        $plan = MemorizationPlan::findOrFail($request->plan_id);
        
        // Verify plan belongs to student
        if ($plan->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to plan'
            ], 403);
        }

        $tajweedAnalysis = null;
        
        // Process audio if provided
        if ($request->hasFile('audio_file')) {
            try {
                $audioFile = $request->file('audio_file');
                $audioPath = $audioFile->store('memorization/audio', 'public');
                
                // Get expected Quranic text for the ayah
                $expectedText = $this->getAyahText($request->surah_id, $request->ayah_id);
                
                // Analyze with Whisper service
                $tajweedAnalysis = $this->whisperService->analyzeRecitation(
                    Storage::url($audioPath),
                    [
                        'verses' => [$expectedText],
                        'area' => 'tajweed',
                        'assignment_type' => 'memorization'
                    ]
                );
                
            } catch (\Exception $e) {
                \Log::error('Audio analysis failed', [
                    'error' => $e->getMessage(),
                    'student_id' => $student->id,
                    'plan_id' => $request->plan_id
                ]);
                
                // Continue without audio analysis
                $tajweedAnalysis = [
                    'accuracy_score' => 75,
                    'feedback_sections' => [
                        'specific_feedback' => 'Audio analysis temporarily unavailable. Review recorded successfully.'
                    ]
                ];
            }
        }

        // Update SRS queue entry
        $srsEntry = SrsQueue::where('student_id', $student->id)
            ->where('memorization_plan_id', $plan->id)
            ->where('surah_id', $request->surah_id)
            ->where('ayah_id', $request->ayah_id)
            ->first();

        if ($srsEntry) {
            $this->updateSrsEntry($srsEntry, $request->confidence_score);
        }

        // Update progress tracking
        $this->updateQuranProgress($student->id, $request->surah_id, $request->ayah_id, $request->confidence_score);

        // Send notification to teachers
        $this->notifyTeachers($student, $plan, $request->surah_id, $request->ayah_id, $request->confidence_score);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'tajweed_analysis' => $tajweedAnalysis
        ]);
    }

    /**
     * Create initial SRS entries for a memorization plan.
     *
     * @param MemorizationPlan $plan The memorization plan
     * @return void
     */
    private function createInitialSrsEntries(MemorizationPlan $plan)
    {
        for ($ayah = $plan->start_ayah; $ayah <= $plan->end_ayah; $ayah++) {
            SrsQueue::create([
                'student_id' => $plan->student_id,
                'memorization_plan_id' => $plan->id,
                'surah_id' => $plan->surah_id,
                'ayah_id' => $ayah,
                'interval_days' => 1,
                'repetitions' => 0,
                'ease_factor' => 2.5,
                'due_at' => now()->addDay(),
                'confidence_score' => 0.0
            ]);
        }
    }

    /**
     * Update SRS entry based on confidence score.
     *
     * @param SrsQueue $srsEntry The SRS queue entry
     * @param float $confidenceScore Confidence score (0-1)
     * @return void
     */
    private function updateSrsEntry(SrsQueue $srsEntry, float $confidenceScore)
    {
        $srsEntry->repetitions++;
        $srsEntry->confidence_score = $confidenceScore;
        
        // SRS algorithm implementation
        if ($confidenceScore >= 0.8) {
            // Good recall - increase interval
            $srsEntry->ease_factor = min(2.5, $srsEntry->ease_factor + 0.1);
            $srsEntry->interval_days = (int) ($srsEntry->interval_days * $srsEntry->ease_factor);
        } elseif ($confidenceScore >= 0.6) {
            // Moderate recall - maintain interval
            $srsEntry->interval_days = max(1, $srsEntry->interval_days);
        } else {
            // Poor recall - reset interval
            $srsEntry->ease_factor = max(1.3, $srsEntry->ease_factor - 0.2);
            $srsEntry->interval_days = 1;
        }
        
        $srsEntry->due_at = now()->addDays($srsEntry->interval_days);
        $srsEntry->save();
    }

    /**
     * Update Quran progress tracking.
     *
     * @param int $studentId Student ID
     * @param int $surahId Surah ID
     * @param int $ayahId Ayah ID
     * @param float $confidenceScore Confidence score
     * @return void
     */
    private function updateQuranProgress(int $studentId, int $surahId, int $ayahId, float $confidenceScore)
    {
        QuranProgress::updateOrCreate(
            [
                'student_id' => $studentId,
                'surah_id' => $surahId,
                'ayah_id' => $ayahId
            ],
            [
                'confidence_score' => $confidenceScore,
                'last_reviewed_at' => now(),
                'review_count' => \DB::raw('review_count + 1')
            ]
        );
    }

    /**
     * Get Quranic text for a specific ayah.
     *
     * @param int $surahId Surah ID
     * @param int $ayahId Ayah ID
     * @return string Arabic text of the ayah
     */
    private function getAyahText(int $surahId, int $ayahId): string
    {
        // This would typically fetch from your Quran database
        // For now, return a placeholder
        return "بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ";
    }

    /**
     * Notify teachers about student progress.
     *
     * @param \App\Models\User $student The student
     * @param MemorizationPlan $plan The memorization plan
     * @param int $surahId Surah ID
     * @param int $ayahId Ayah ID
     * @param float $confidenceScore Confidence score
     * @return void
     */
    private function notifyTeachers($student, MemorizationPlan $plan, int $surahId, int $ayahId, float $confidenceScore)
    {
        // Get student's teachers
        $teachers = $student->teachers ?? collect();
        
        foreach ($teachers as $teacher) {
            $teacher->notify(new MemorizationProgressUpdated(
                $student,
                $plan,
                $surahId,
                $ayahId,
                $confidenceScore
            ));
        }
    }

    /**
     * Get student memorization progress for teacher oversight
     * @param Request $request HTTP request with optional class_id filter
     * @return JsonResponse Student progress data
     */
    public function getStudentProgress(Request $request)
    {
        $user = $request->user();
        
        // Ensure user is a teacher
        if (!$user->hasRole('teacher')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $classId = $request->query('class_id');
        
        // Build query for memorization plans with student progress
        $query = MemorizationPlan::with(['user', 'srsQueue'])
            ->join('users', 'memorization_plans.user_id', '=', 'users.id');
            
        // Filter by class if specified
        if ($classId && $classId !== 'all') {
            $query->join('class_members', 'users.id', '=', 'class_members.user_id')
                  ->where('class_members.class_id', $classId);
        }
        
        // Get teacher's students only
        $teacherStudentIds = $user->students()->pluck('users.id');
        $query->whereIn('users.id', $teacherStudentIds);
        
        $plans = $query->get();
        
        $progressData = $plans->map(function ($plan) {
            $totalAyahs = $plan->end_ayah - $plan->start_ayah + 1;
            $memorizedAyahs = $plan->srsQueue->where('confidence', '>=', 0.8)->count();
            $dueReviews = $plan->srsQueue->where('next_review', '<=', now())->count();
            $avgConfidence = $plan->srsQueue->avg('confidence') ?? 0;
            
            return [
                'id' => $plan->id,
                'student_name' => $plan->user->name,
                'student_id' => $plan->user->id,
                'plan_title' => "Surah {$plan->surah_id}: {$plan->start_ayah}-{$plan->end_ayah}",
                'total_ayahs' => $totalAyahs,
                'memorized_ayahs' => $memorizedAyahs,
                'due_reviews' => $dueReviews,
                'last_activity' => $plan->updated_at->toISOString(),
                'confidence_avg' => $avgConfidence,
                'streak_days' => $this->calculateStreakDays($plan->user->id),
                'hasanat_earned' => $plan->user->quranProgress->sum('hasanat')
            ];
        });
        
        return response()->json($progressData);
    }

    /**
     * Get student memorization statistics for teacher oversight
     * @param Request $request HTTP request with optional class_id filter
     * @return JsonResponse Student statistics data
     */
    public function getStudentStats(Request $request)
    {
        $user = $request->user();
        
        // Ensure user is a teacher
        if (!$user->hasRole('teacher')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $classId = $request->query('class_id');
        
        // Get teacher's students
        $studentsQuery = $user->students();
        
        // Filter by class if specified
        if ($classId && $classId !== 'all') {
            $studentsQuery->whereHas('classMemberships', function ($query) use ($classId) {
                $query->where('class_id', $classId);
            });
        }
        
        $students = $studentsQuery->with(['memorizationPlans.srsQueue', 'quranProgress'])->get();
        
        $statsData = $students->map(function ($student) {
            $activePlans = $student->memorizationPlans->where('status', 'active')->count();
            $totalMemorized = $student->memorizationPlans
                ->flatMap(function ($plan) {
                    return $plan->srsQueue->where('confidence', '>=', 0.8);
                })->count();
            
            $weeklyReviews = $student->memorizationPlans
                ->flatMap(function ($plan) {
                    return $plan->srsQueue->where('last_reviewed', '>=', now()->subWeek());
                })->count();
            
            $avgConfidence = $student->memorizationPlans
                ->flatMap(function ($plan) {
                    return $plan->srsQueue;
                })->avg('confidence') ?? 0;
            
            $lastActivity = $student->memorizationPlans->max('updated_at') ?? $student->updated_at;
            
            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'active_plans' => $activePlans,
                'total_memorized' => $totalMemorized,
                'weekly_reviews' => $weeklyReviews,
                'avg_confidence' => $avgConfidence,
                'last_seen' => $lastActivity->toISOString()
            ];
        });
        
        return response()->json($statsData);
    }

    /**
     * Calculate streak days for a student
     * @param int $userId Student user ID
     * @return int Number of consecutive days with memorization activity
     */
    private function calculateStreakDays($userId)
    {
        $recentActivity = SrsQueue::where('user_id', $userId)
            ->where('last_reviewed', '>=', now()->subDays(30))
            ->orderBy('last_reviewed', 'desc')
            ->pluck('last_reviewed')
            ->map(function ($date) {
                return $date->format('Y-m-d');
            })
            ->unique()
            ->values();
        
        if ($recentActivity->isEmpty()) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = now()->format('Y-m-d');
        
        foreach ($recentActivity as $activityDate) {
            if ($activityDate === $currentDate) {
                $streak++;
                $currentDate = now()->subDay()->format('Y-m-d');
            } else {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Get comprehensive memorization analytics for admin dashboard.
     * Returns system-wide statistics, top memorizers, and weekly progress trends.
     */
    public function getMemorizationAnalytics(Request $request)
    {
        try {
            // Total memorizers (users with memorization plans)
            $totalMemorizers = MemorizationPlan::distinct('user_id')->count();

            // Total ayahs memorized across all users
            $totalAyahsMemorized = QuranProgress::where('memorized_confidence', '>=', 0.8)
                ->count();

            // Average progress (percentage of ayahs with high confidence)
            $averageProgress = $totalMemorizers > 0 
                ? round(($totalAyahsMemorized / ($totalMemorizers * 6236)) * 100, 1) // 6236 total ayahs in Quran
                : 0;

            // Top 10 memorizers with their stats
            $topMemorizers = User::select('users.id', 'users.name')
                ->join('quran_progress', 'users.id', '=', 'quran_progress.user_id')
                ->selectRaw('COUNT(CASE WHEN quran_progress.memorized_confidence >= 0.8 THEN 1 END) as ayahs_memorized')
                ->groupBy('users.id', 'users.name')
                ->orderBy('ayahs_memorized', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'ayahsMemorized' => $user->ayahs_memorized,
                        'currentStreak' => $this->calculateStreakDays($user->id)
                    ];
                });

            // Weekly progress for the last 7 days
            $weeklyProgress = collect(range(6, 0))->map(function ($daysAgo) {
                $date = now()->subDays($daysAgo);
                $startOfDay = $date->copy()->startOfDay();
                $endOfDay = $date->copy()->endOfDay();

                $ayahsCount = QuranProgress::whereBetween('updated_at', [$startOfDay, $endOfDay])
                    ->where('memorized_confidence', '>=', 0.8)
                    ->count();

                $usersCount = QuranProgress::whereBetween('updated_at', [$startOfDay, $endOfDay])
                    ->where('memorized_confidence', '>=', 0.8)
                    ->distinct('user_id')
                    ->count();

                return [
                    'date' => $date->format('Y-m-d'),
                    'ayahs' => $ayahsCount,
                    'users' => $usersCount
                ];
            });

            return response()->json([
                'totalMemorizers' => $totalMemorizers,
                'totalAyahsMemorized' => $totalAyahsMemorized,
                'averageProgress' => $averageProgress,
                'topMemorizers' => $topMemorizers,
                'weeklyProgress' => $weeklyProgress
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch memorization analytics: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch memorization analytics'
            ], 500);
        }
    }
}