<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MemorizationPlan;
use App\Models\QuranProgress;
use App\Models\SrsQueue;
use App\Models\User;
use App\Notifications\MemorizationProgressUpdated;
use App\Services\WhisperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
    public function index(Request $request)
    {
        $user = $request->user();

        $plans = MemorizationPlan::with(['srsQueues' => function ($query) {
                $query->orderBy('due_at');
            }])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $payload = $plans->map(function (MemorizationPlan $plan) {
            return [
                'id' => $plan->id,
                'title' => $plan->title,
                'status' => $plan->status,
                'surahs' => $plan->surahs,
                'daily_target' => $plan->daily_target,
                'start_date' => optional($plan->start_date)->toDateString(),
                'end_date' => optional($plan->end_date)->toDateString(),
                'stats' => $this->buildPlanStatsPayload($plan),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * Create a new memorization plan.
     *
     * @param Request $request HTTP request with plan data
     * @return \Illuminate\Http\JsonResponse JSON response with created plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'surahs' => 'required|array|min:1',
            'surahs.*' => 'integer|min:1|max:114',
            'daily_target' => 'required|integer|min:1|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'is_teacher_visible' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        $plan = MemorizationPlan::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'surahs' => $validated['surahs'],
            'daily_target' => $validated['daily_target'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_teacher_visible' => $validated['is_teacher_visible'] ?? true,
            'status' => 'active',
        ]);

        $this->initializeSrsQueue($plan);
        $plan->load('srsQueues');

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'stats' => $this->buildPlanStatsPayload($plan),
            ],
        ], 201);
    }

    public function planStats(Request $request, MemorizationPlan $plan)
    {
        $user = $request->user();

        if ($plan->user_id !== $user->id && !$user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to plan stats',
            ], 403);
        }

        $plan->load('srsQueues');

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'stats' => $this->buildPlanStatsPayload($plan),
            ],
        ]);
    }

    /**
     * Get due reviews for the student.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with due reviews
     */
    public function getDueReviews(Request $request)
    {
        $user = $request->user();

        $dueReviews = SrsQueue::with('plan')
            ->where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit(20)
            ->get();

        $data = $dueReviews->map(function (SrsQueue $queue) {
            $overdueHours = $queue->due_at && $queue->due_at->isPast()
                ? $queue->due_at->diffInHours(now())
                : 0;

            return [
                'id' => $queue->id,
                'plan_id' => $queue->plan_id,
                'plan_title' => optional($queue->plan)->title,
                'surah_id' => $queue->surah_id,
                'ayah_id' => $queue->ayah_id,
                'confidence_score' => $queue->confidence_score,
                'repetitions' => $queue->repetitions,
                'due_at' => optional($queue->due_at)->toISOString(),
                'overdue_hours' => $overdueHours,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Submit a memorization review with audio analysis.
     *
     * @param Request $request HTTP request with review data and audio
     * @return \Illuminate\Http\JsonResponse JSON response with analysis results
     */
    public function reviewAyah(Request $request)
    {
        if ($request->missing('ayah_number') && $request->has('ayah_id')) {
            $request->merge(['ayah_number' => (int) $request->input('ayah_id')]);
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:memorization_plans,id',
            'surah_id' => 'required|integer|min:1|max:114',
            'ayah_number' => 'required|integer|min:1',
            'confidence_score' => 'required|numeric|min:0|max:1',
            'time_spent' => 'nullable|integer|min:1',
            'audio_file' => 'nullable|file|mimes:wav,mp3,m4a,ogg|max:10240',
        ]);

        $user = $request->user();
        $plan = MemorizationPlan::findOrFail($validated['plan_id']);

        if ($plan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to plan',
            ], 403);
        }

        $tajweedAnalysis = null;

        if ($request->hasFile('audio_file')) {
            try {
                $audioFile = $request->file('audio_file');
                $disk = config('filesystems.default', 'public');
                $audioPath = $audioFile->store('memorization/audio/' . $user->id, $disk);

                $expectedText = $this->getAyahText($validated['surah_id'], $validated['ayah_number']);

                $tajweedAnalysis = $this->whisperService->analyzeRecitation(
                    Storage::disk($disk)->url($audioPath),
                    [
                        'verses' => array_filter([$expectedText]),
                        'area' => 'tajweed',
                        'assignment_type' => 'memorization',
                    ]
                );
            } catch (\Exception $e) {
                \Log::error('Audio analysis failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ]);

                $tajweedAnalysis = [
                    'success' => false,
                    'message' => 'Audio analysis temporarily unavailable. Review recorded successfully.',
                ];
            }
        }

        $srsEntry = SrsQueue::firstOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'surah_id' => $validated['surah_id'],
                'ayah_id' => $validated['ayah_number'],
            ],
            [
                'ease_factor' => 2.5,
                'interval' => 1,
                'repetitions' => 0,
                'confidence_score' => 0,
                'due_at' => now(),
            ]
        );

        $srsEntry->applyReview((float) $validated['confidence_score']);

        $ayahNumber = (int) $validated['ayah_number'];

        $this->updateQuranProgress(
            $user->id,
            (int) $validated['surah_id'],
            $ayahNumber,
            (float) $validated['confidence_score']
        );

        $this->notifyTeachers($user, $plan, (int) $validated['surah_id'], $ayahNumber, (float) $validated['confidence_score']);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'tajweed_analysis' => $tajweedAnalysis,
        ]);
    }

    /**
     * Create initial SRS entries for a memorization plan.
     *
     * @param MemorizationPlan $plan The memorization plan
     * @return void
     */
    private function initializeSrsQueue(MemorizationPlan $plan): void
    {
        $surahs = is_array($plan->surahs) ? $plan->surahs : [];

        if (empty($surahs)) {
            return;
        }

        $startDate = $plan->start_date instanceof Carbon ? $plan->start_date : Carbon::parse($plan->start_date);

        foreach ($surahs as $surahId) {
            $ayahCount = $this->getSurahAyahCount((int) $surahId);
            $limit = max(1, (int) $plan->daily_target);

            for ($ayah = 1; $ayah <= min($ayahCount, $limit); $ayah++) {
                SrsQueue::firstOrCreate(
                    [
                        'user_id' => $plan->user_id,
                        'plan_id' => $plan->id,
                        'surah_id' => $surahId,
                        'ayah_id' => $ayah,
                    ],
                    [
                        'ease_factor' => 2.5,
                        'interval' => 1,
                        'repetitions' => 0,
                        'confidence_score' => 0,
                        'due_at' => $startDate,
                    ]
                );
            }
        }
    }

    private function buildPlanStatsPayload(MemorizationPlan $plan): array
    {
        $queueItems = $plan->relationLoaded('srsQueues')
            ? $plan->srsQueues
            : $plan->srsQueues()->get();

        $total = $queueItems->count();
        $dueToday = $queueItems->filter(function (SrsQueue $item) {
            return $item->due_at && $item->due_at->isBefore(now()->endOfDay());
        })->count();

        $overdue = $queueItems->filter(function (SrsQueue $item) {
            return $item->due_at && $item->due_at->isPast();
        })->count();

        $mastered = $queueItems->filter(function (SrsQueue $item) {
            return $item->confidence_score >= 0.9 && $item->repetitions >= 3;
        })->count();

        $averageConfidence = $queueItems->avg('confidence_score') ?? 0;

        $nextReview = $queueItems->filter(fn (SrsQueue $item) => $item->due_at)
            ->sortBy('due_at')
            ->first();

        return [
            'total_items' => $total,
            'mastered_items' => $mastered,
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'average_confidence' => round($averageConfidence, 2),
            'completion_percentage' => $total > 0 ? round(($mastered / $total) * 100, 2) : 0.0,
            'next_review_at' => $nextReview && $nextReview->due_at
                ? $nextReview->due_at->toISOString()
                : null,
        ];
    }

    private function getSurahAyahCount(int $surahId): int
    {
        $ayahCounts = [
            1 => 7, 2 => 286, 3 => 200, 4 => 176, 5 => 120, 6 => 165, 7 => 206, 8 => 75, 9 => 129, 10 => 109,
            11 => 123, 12 => 111, 13 => 43, 14 => 52, 15 => 99, 16 => 128, 17 => 111, 18 => 110, 19 => 98, 20 => 135,
            21 => 112, 22 => 78, 23 => 118, 24 => 64, 25 => 77, 26 => 227, 27 => 93, 28 => 88, 29 => 69, 30 => 60,
            31 => 34, 32 => 30, 33 => 73, 34 => 54, 35 => 45, 36 => 83, 37 => 182, 38 => 88, 39 => 75, 40 => 85,
            41 => 54, 42 => 53, 43 => 89, 44 => 59, 45 => 37, 46 => 35, 47 => 38, 48 => 29, 49 => 18, 50 => 45,
            51 => 60, 52 => 49, 53 => 62, 54 => 55, 55 => 78, 56 => 96, 57 => 29, 58 => 22, 59 => 24, 60 => 13,
            61 => 14, 62 => 11, 63 => 11, 64 => 18, 65 => 12, 66 => 12, 67 => 30, 68 => 52, 69 => 52, 70 => 44,
            71 => 28, 72 => 28, 73 => 20, 74 => 56, 75 => 40, 76 => 31, 77 => 50, 78 => 40, 79 => 46, 80 => 42,
            81 => 29, 82 => 19, 83 => 36, 84 => 25, 85 => 22, 86 => 17, 87 => 19, 88 => 26, 89 => 30, 90 => 20,
            91 => 15, 92 => 21, 93 => 11, 94 => 8, 95 => 8, 96 => 19, 97 => 5, 98 => 8, 99 => 8, 100 => 11,
            101 => 11, 102 => 8, 103 => 3, 104 => 9, 105 => 5, 106 => 4, 107 => 7, 108 => 3, 109 => 6, 110 => 3,
            111 => 5, 112 => 4, 113 => 5, 114 => 6,
        ];

        return $ayahCounts[$surahId] ?? 10;
    }

    /**
     * Update Quran progress tracking.
     *
     * @param int $studentId Student ID
     * @param int $surahId Surah ID
     * @param int $ayahNumber Ayah number within the surah
     * @param float $confidenceScore Confidence score
     * @return void
     */
    private function updateQuranProgress(int $studentId, int $surahId, int $ayahNumber, float $confidenceScore)
    {
        $attributes = [
            'memorized_confidence' => $confidenceScore,
            'last_seen_at' => now(),
        ];

        if (Schema::hasColumn('quran_progress', 'memorization_reviews')) {
            $attributes['memorization_reviews'] = DB::raw('COALESCE(memorization_reviews, 0) + 1');
        }

        QuranProgress::updateOrCreate(
            [
                'user_id' => $studentId,
                'surah_id' => $surahId,
                'ayah_number' => $ayahNumber,
            ],
            $attributes
        );
    }

    /**
     * Get Quranic text for a specific ayah.
     *
     * @param int $surahId Surah ID
     * @param int $ayahNumber Ayah number within the surah
     * @return string Arabic text of the ayah
     */
    private function getAyahText(int $surahId, int $ayahNumber): string
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
     * @param int $ayahNumber Ayah number within the surah
     * @param float $confidenceScore Confidence score
     * @return void
     */
    private function notifyTeachers($student, MemorizationPlan $plan, int $surahId, int $ayahNumber, float $confidenceScore)
    {
        // Get student's teachers
        $teachers = $student->teachers ?? collect();

        foreach ($teachers as $teacher) {
            $teacher->notify(new MemorizationProgressUpdated(
                $student,
                $plan,
                $surahId,
                $ayahNumber,
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
        $query = MemorizationPlan::with(['user', 'srsQueues'])
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
            $queue = $plan->srsQueues ?? collect();
            $totalAyahs = $queue->count();
            $memorizedAyahs = $queue->where('confidence_score', '>=', 0.8)->count();
            $dueReviews = $queue->where('due_at', '<=', now())->count();
            $avgConfidence = $queue->avg('confidence_score') ?? 0;

            return [
                'id' => $plan->id,
                'student_name' => $plan->user->name,
                'student_id' => $plan->user->id,
                'plan_title' => $plan->title,
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
        
        $students = $studentsQuery->with(['memorizationPlans.srsQueues', 'quranProgress'])->get();

        $statsData = $students->map(function ($student) {
            $activePlans = $student->memorizationPlans->where('status', 'active')->count();
            $totalMemorized = $student->memorizationPlans
                ->flatMap(function ($plan) {
                    return $plan->srsQueues->where('confidence_score', '>=', 0.8);
                })->count();

            $weeklyReviews = $student->memorizationPlans
                ->flatMap(function ($plan) {
                    return $plan->srsQueues->where('updated_at', '>=', now()->subWeek());
                })->count();

            $avgConfidence = $student->memorizationPlans
                ->flatMap(function ($plan) {
                    return $plan->srsQueues;
                })->avg('confidence_score') ?? 0;
            
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
            ->where('updated_at', '>=', now()->subDays(30))
            ->orderBy('updated_at', 'desc')
            ->pluck('updated_at')
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