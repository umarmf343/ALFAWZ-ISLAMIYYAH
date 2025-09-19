<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Quran\SurahController;
use App\Http\Controllers\Core\ClassController;
use App\Http\Controllers\Core\AssignmentController;
use App\Http\Controllers\Core\HotspotController;
use App\Http\Controllers\Core\SubmissionController;
use App\Http\Controllers\Core\FeedbackController;
use App\Http\Controllers\Core\LeaderboardController;
use App\Http\Controllers\Core\ProfileController;
use App\Http\Controllers\Core\PaymentController;
use App\Http\Controllers\Core\ReaderController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\TeacherController as MainTeacherController;
use App\Http\Controllers\Core\FileUploadController;
use App\Http\Controllers\Core\SrsController;
use App\Http\Controllers\Core\MediaController;
use App\Http\Controllers\Core\ResourceController;
use App\Http\Controllers\Admin\AdminOverviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminClassController;
use App\Http\Controllers\Admin\AdminAssignmentController;
use App\Http\Controllers\Admin\AdminSubmissionController;
use App\Http\Controllers\Admin\AdminWhisperController;
use App\Http\Controllers\Admin\AdminLeaderboardController;
use App\Http\Controllers\Admin\AdminBillingController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminFlagsController;
use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminAssetController;
use App\Http\Controllers\Admin\AdminToolsController;
use App\Http\Controllers\Admin\AdminOrgController;
use App\Http\Controllers\Teacher\TeacherAnalyticsController;
use App\Http\Controllers\RecitationController;
use App\Http\Controllers\TajweedController;
use App\Http\Controllers\Core\NotificationController;
use App\Http\Controllers\MemorizationController;

/**
 * Health check endpoint for monitoring
 */
Route::get('/health', fn () => response()->json([
    'ok' => true,
    'ts' => now()->toISOString(),
    'app' => config('app.name'),
    'version' => '1.0.0'
]));

/**
 * Broadcasting authentication routes
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });
});

/**
 * Authentication routes (public)
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', MeController::class);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        
        // Email verification routes
        Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);
        Route::get('/email/verification-status', [AuthController::class, 'checkEmailVerification']);
    });
});

/**
 * Admin API routes (require admin role)
 */
Route::middleware(['auth:sanctum', 'can:admin'])
    ->prefix('admin')->group(function () {
    
    // Overview dashboard
    Route::get('/overview', [AdminOverviewController::class, 'metrics']);
    
    // Users & Roles management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::match(['post', 'put'], '/users/{id}/role', [AdminUserController::class, 'updateRole']);
    Route::post('/users/bulk-role', [AdminUserController::class, 'bulkRole']);
    Route::post('/users/{id}/impersonate', [AdminUserController::class, 'impersonate']);
    Route::put('/users/{id}/suspend', [AdminUserController::class, 'updateStatus']);
    Route::put('/users/{id}/status', [AdminUserController::class, 'updateStatus']);
    
    // Teachers & Classes management
    Route::get('/classes', [AdminClassController::class, 'index']);
    Route::post('/classes', [AdminClassController::class, 'store']);
    Route::post('/classes/{id}/assign-teacher', [AdminClassController::class, 'assignTeacher']);
    
    // Assignments & Assets management
    Route::get('/assignments', [AdminAssignmentController::class, 'index']);
    Route::get('/assets', [AdminAssetController::class, 'index']);
    Route::post('/assets/sign', [AdminAssetController::class, 'signUpload']);
    
    // Submissions & Whisper Jobs monitoring
    Route::get('/submissions', [AdminSubmissionController::class, 'index']);
    Route::post('/submissions/bulk-update', [AdminSubmissionController::class, 'bulkUpdate']);
    Route::get('/whisper/jobs', [AdminWhisperController::class, 'index']);
    Route::get('/whisper/jobs/{id}', [AdminWhisperController::class, 'show']);
    
    // Leaderboards & Gamification
    Route::get('/leaderboards/snapshots', [AdminLeaderboardController::class, 'snapshots']);
    Route::post('/leaderboards/regenerate', [AdminLeaderboardController::class, 'regenerate']);
    
    // Payments & Plans management
    Route::get('/plans', [AdminBillingController::class, 'plans']);
    Route::post('/plans', [AdminBillingController::class, 'upsertPlan']);
    Route::get('/invoices', [AdminBillingController::class, 'invoices']);
    Route::post('/invoices/{id}/refund', [AdminBillingController::class, 'refund']);
    Route::post('/invoices/bulk-refund', [AdminBillingController::class, 'bulkRefund']);
    
    // Analytics dashboard
    Route::get('/analytics', [AdminAnalyticsController::class, 'dashboard']);
    
    // Organization Settings
    Route::get('/org', [AdminOrgController::class, 'show']);
    Route::patch('/org', [AdminOrgController::class, 'update']);
    Route::get('/org/summary', [AdminOrgController::class, 'summary']);
    
    // Settings & Feature Flags
    Route::get('/settings', [AdminSettingsController::class, 'all']);
    Route::post('/settings', [AdminSettingsController::class, 'upsert']);
    Route::get('/flags', [AdminFlagsController::class, 'index']);
    Route::post('/flags/{key}', [AdminFlagsController::class, 'toggle']);
    
    // Audit Logs
    Route::get('/audits', [AdminAuditController::class, 'index']);
    
    // Admin Tools (Paystack, S3, Queue)
    Route::prefix('tools')->group(function () {
        // Paystack webhook testing
        Route::post('/test-webhook', [AdminToolsController::class, 'testPaystackWebhook']);
        Route::post('/verify', [AdminToolsController::class, 'verifyPaystackRef']);
        
        // S3 health checks
        Route::get('/s3/health', [AdminToolsController::class, 's3Health']);
        Route::post('/s3/sign', [AdminToolsController::class, 'signS3Upload']);
        
        // Queue system ping
        Route::get('/queue/ping', [AdminToolsController::class, 'queuePing']);
    });
});

/**
 * Protected API routes (require authentication)
 */
Route::middleware('auth:sanctum')->group(function () {
    
    // Simple test endpoint
    Route::get('/test-auth', function () {
        return response()->json(['message' => 'Authentication working', 'user_id' => auth()->id()]);
    });
    
    // Student Dashboard and Memorization
    Route::prefix('student')->group(function () {
        Route::get('/dashboard', [StudentController::class, 'getDashboard']);
        Route::post('/recitation', [StudentController::class, 'updateRecitation']);
        Route::get('/ayah-of-day', [StudentController::class, 'getAyahOfDay']);
        Route::get('/recommendations', [StudentController::class, 'getRecommendations']);
        Route::get('/weekly-progress', [StudentController::class, 'getWeeklyProgress']);
        
        // Memorization routes (moved to dedicated controller)
        Route::prefix('memorization')->group(function () {
            Route::get('/plans', [MemorizationController::class, 'index']);
            Route::post('/plans', [MemorizationController::class, 'store']);
            Route::get('/plans/{plan}/stats', [MemorizationController::class, 'planStats']);
            Route::get('/due-reviews', [MemorizationController::class, 'getDueReviews']);
            Route::post('/review', [MemorizationController::class, 'reviewAyah']);
        });
        
        // Teacher memorization oversight endpoints
        Route::get('/teacher/memorization/students', [StudentController::class, 'getMemorizationStudents']);
        Route::get('/teacher/memorization/analytics', [StudentController::class, 'getMemorizationAnalytics']);
        Route::get('/teacher/memorization/audio-reviews', [StudentController::class, 'getAudioReviews']);
        Route::post('/teacher/memorization/reviews/{reviewId}/{action}', [StudentController::class, 'reviewAudioSubmission'])
            ->where('action', 'approve|reject');
        
        // Additional teacher memorization oversight routes
        Route::get('/teacher/memorization-progress', [MemorizationController::class, 'getStudentProgress']);
        Route::get('/teacher/memorization-stats', [MemorizationController::class, 'getStudentStats']);
        
        // Admin memorization analytics
        Route::get('/admin/memorization-analytics', [MemorizationController::class, 'getMemorizationAnalytics']);
        
        // Leaderboard community features
        Route::prefix('leaderboard')->group(function () {
            Route::get('/', [StudentController::class, 'getLeaderboard']);
            Route::post('/invite', [StudentController::class, 'sendLeaderboardInvite']);
            Route::get('/invites', [StudentController::class, 'getLeaderboardInvites']);
            Route::post('/invites/{invite}/respond', [StudentController::class, 'respondToLeaderboardInvite']);
            Route::post('/reminder', [StudentController::class, 'sendQuranReminder']);
            Route::put('/preferences', [StudentController::class, 'updateLeaderboardPreferences']);
            Route::get('/friends', [StudentController::class, 'getLeaderboardFriends']);
        });
    });
    
    // Direct me endpoint for frontend compatibility
    Route::get('/me', [ProfileController::class, 'me']);
    
    // Quran API integration
    Route::prefix('quran')->group(function () {
        Route::get('/surahs', [SurahController::class, 'index']);
        Route::get('/surahs/{id}', [SurahController::class, 'show']);
        Route::get('/surahs/{id}/ayahs', [SurahController::class, 'ayahs']);
        Route::get('/surahs/{surahId}/ayahs/{ayahId}', [SurahController::class, 'getAyah']);
        Route::get('/recitations', [SurahController::class, 'recitations']);
    });
    
    // Profile and user management
    Route::prefix('profile')->group(function () {
        Route::get('/me', [ProfileController::class, 'me']);
        Route::put('/me', [ProfileController::class, 'update']);
        Route::get('/my-teachers', [ProfileController::class, 'myTeachers']);
        Route::get('/my-students', [ProfileController::class, 'myStudents']);
        Route::get('/stats', [ProfileController::class, 'stats']);
        Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
        Route::delete('/avatar', [ProfileController::class, 'removeAvatar']);
    });
    
    // Class management
    Route::apiResource('classes', ClassController::class);
    Route::prefix('classes/{class}')->group(function () {
        Route::post('/members', [ClassController::class, 'addMember']);
        Route::delete('/members/{user}', [ClassController::class, 'removeMember']);
        Route::get('/members', [ClassController::class, 'getMembers']);
        Route::get('/stats', [ClassController::class, 'getStats']);
        Route::post('/invite', [ClassController::class, 'inviteStudent']);
    });
    
    // Assignment management
    Route::apiResource('assignments', AssignmentController::class);
    Route::prefix('assignments/{assignment}')->group(function () {
        Route::post('/publish', [AssignmentController::class, 'publish']);
        Route::post('/unpublish', [AssignmentController::class, 'unpublish']);
        Route::get('/submissions', [AssignmentController::class, 'getSubmissions']);
        Route::post('/duplicate', [AssignmentController::class, 'duplicate']);
        Route::get('/stats', [AssignmentController::class, 'getStats']);
    });
    
    // Hotspot management (nested under assignments)
    Route::apiResource('assignments.hotspots', HotspotController::class)->shallow();
    Route::prefix('hotspots/{hotspot}')->group(function () {
        Route::post('/interact', [HotspotController::class, 'recordInteraction']);
        Route::get('/interactions', [HotspotController::class, 'getInteractions']);
        Route::get('/stats', [HotspotController::class, 'getStats']);
    });
    
    // Submission management
    Route::apiResource('submissions', SubmissionController::class)->except(['destroy']);
    Route::prefix('submissions/{submission}')->group(function () {
        Route::post('/audio', [SubmissionController::class, 'uploadAudio']);
        Route::delete('/audio', [SubmissionController::class, 'deleteAudio']);
        Route::post('/submit', [SubmissionController::class, 'submitForGrading']);
        Route::post('/resubmit', [SubmissionController::class, 'resubmit']);
    });
    
    // File Uploads
    Route::post('/upload/audio', [FileUploadController::class, 'uploadAudio']);
    Route::post('/upload/image', [FileUploadController::class, 'uploadImage']);
    Route::delete('/upload/file', [FileUploadController::class, 'deleteFile']);
    
    // Feedback management
    Route::prefix('feedback')->group(function () {
        Route::post('/submissions/{submission}', [FeedbackController::class, 'store']);
        Route::put('/{feedback}', [FeedbackController::class, 'update']);
        Route::delete('/{feedback}', [FeedbackController::class, 'destroy']);
        Route::post('/{feedback}/audio', [FeedbackController::class, 'uploadAudio']);
    });
    
    // Leaderboard and rankings
    Route::prefix('leaderboard')->group(function () {
        Route::get('/', [LeaderboardController::class, 'index']);
        Route::get('/class/{class}', [LeaderboardController::class, 'classLeaderboard']);
        Route::get('/global', [LeaderboardController::class, 'globalLeaderboard']);
        Route::get('/weekly', [LeaderboardController::class, 'weeklyLeaderboard']);
        Route::get('/monthly', [LeaderboardController::class, 'monthlyLeaderboard']);
        Route::get('/hasanat-history', [LeaderboardController::class, 'hasanatHistory']);
    });
    
    // Payment management (protected routes)
    Route::prefix('payments')->group(function () {
        Route::post('/initialize', [PaymentController::class, 'initialize']);
        Route::get('/history', [PaymentController::class, 'history']);
        Route::get('/subscription', [PaymentController::class, 'subscription']);
        Route::post('/cancel-subscription', [PaymentController::class, 'cancelSubscription']);
        Route::get('/plans', [PaymentController::class, 'plans']);
    });
    
    // Spaced Repetition System (SRS) management
    Route::prefix('srs')->group(function () {
        Route::get('/due', [SrsController::class, 'getDueItems']);
        Route::get('/queue', [SrsController::class, 'getQueue']);
        Route::get('/stats', [SrsController::class, 'getStats']);
        Route::post('/add', [SrsController::class, 'addToQueue']);
        Route::post('/review', [SrsController::class, 'submitReview']);
        Route::delete('/{id}', [SrsController::class, 'removeFromQueue']);
        Route::post('/{id}/reset', [SrsController::class, 'resetItem']);
    });
    
    // Memorization Management (dedicated routes)
    Route::prefix('memorization')->group(function () {
        Route::get('/plans', [MemorizationController::class, 'index']);
        Route::post('/plans', [MemorizationController::class, 'store']);
        Route::get('/plans/{plan}/stats', [MemorizationController::class, 'planStats']);
        Route::get('/due-reviews', [MemorizationController::class, 'getDueReviews']);
        Route::post('/review', [MemorizationController::class, 'reviewAyah']);
    });
    
    // Resource management (file upload/download with S3)
    Route::apiResource('resources', ResourceController::class);
    Route::prefix('resources/{resource}')->group(function () {
        Route::get('/download', [ResourceController::class, 'download']);
    });
    

    
    // Recitation and Tajweed analysis
    Route::prefix('recitations')->group(function () {
        Route::post('/upload-url', [RecitationController::class, 'uploadUrl']);
        Route::post('/submit', [RecitationController::class, 'submit']);
        Route::get('/status/{id}', [RecitationController::class, 'status']);
    });
    
    // Tajweed Analysis API
    Route::prefix('tajweed')->group(function () {
        // Student routes
        Route::post('/analyze', [TajweedController::class, 'analyzeRecitation']);
        Route::get('/status/{jobId}', [TajweedController::class, 'getAnalysisStatus']);
        Route::get('/history', [TajweedController::class, 'getAnalysisHistory']);
        Route::get('/settings', [TajweedController::class, 'getSettings']);
        
        // Teacher/Admin routes
        Route::middleware('can:teacher-or-admin')->group(function () {
            Route::get('/analytics', [TajweedController::class, 'getAnalytics']);
            Route::post('/reprocess/{jobId}', [TajweedController::class, 'reprocessAnalysis']);
        });
        
        // Admin-only routes
        Route::middleware('can:admin')->group(function () {
            Route::post('/settings', [TajweedController::class, 'updateSettings']);
        });
    });
    
    // Student-specific routes
    Route::prefix('student')->group(function () {
        // Student dashboard data
        Route::get('/stats', [ProfileController::class, 'studentStats']);
        Route::get('/recent-reads', [ProfileController::class, 'recentReads']);
        Route::get('/ayah-of-day', [ProfileController::class, 'ayahOfDay']);
        Route::get('/assignments', [AssignmentController::class, 'studentAssignments']);
        Route::get('/submissions', [SubmissionController::class, 'studentSubmissions']);
        Route::post('/submissions', [SubmissionController::class, 'store']);
        Route::get('/classes', [ClassController::class, 'studentClasses']);
        Route::get('/progress', [ProfileController::class, 'studentProgress']);
        Route::get('/preferences', [ProfileController::class, 'getPreferences']);
        Route::post('/preferences', [ProfileController::class, 'updatePreferences']);
        Route::get('/leaderboard', [LeaderboardController::class, 'studentLeaderboard']);
        Route::get('/memorization-plan', [ProfileController::class, 'getMemorizationPlan']);
        Route::post('/memorization-plan', [ProfileController::class, 'updateMemorizationPlan']);
        Route::get('/srs-queue', [SrsController::class, 'getQueue']);
        Route::post('/srs-review', [SrsController::class, 'submitReview']);
        
        // Student Dashboard APIs
        Route::get('/dashboard', [App\Http\Controllers\Api\StudentController::class, 'getDashboard']);
        Route::post('/recite', [App\Http\Controllers\Api\StudentController::class, 'updateRecitation']);
        Route::get('/recommendations', [App\Http\Controllers\Api\StudentController::class, 'getRecommendations']);
        Route::get('/weekly-progress', [App\Http\Controllers\Api\StudentController::class, 'getWeeklyProgress']);
        
        // Reader state management
        Route::get('/reader/state', [ProfileController::class, 'getReaderState']);
        Route::post('/reader/state', [ProfileController::class, 'saveReaderState']);
        Route::get('/reader/bookmarks', [ProfileController::class, 'getBookmarks']);
        Route::post('/reader/bookmarks', [ProfileController::class, 'addBookmark']);
        Route::delete('/reader/bookmarks/{id}', [ProfileController::class, 'removeBookmark']);
        Route::post('/reader/mark-read', [ProfileController::class, 'markAyahRead']);
    });
    
    // Reader endpoints
    Route::prefix('reader')->group(function () {
        Route::get('/state', [ReaderController::class, 'getState']);
        Route::post('/state', [ReaderController::class, 'saveState']);
        Route::get('/reciters', [ReaderController::class, 'getReciters']);
    });
     
     // Feedback endpoints
     Route::get('/feedback', [FeedbackController::class, 'studentFeedback']);
     
     // Resources endpoints
     Route::get('/resources', [ResourceController::class, 'index']);
     Route::get('/resources/{id}/download', [ResourceController::class, 'download']);
     
     // Notification management
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/{notification}', [NotificationController::class, 'show']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        Route::delete('/read/all', [NotificationController::class, 'deleteAllRead']);
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        Route::post('/preferences', [NotificationController::class, 'updatePreferences']);
    });
    
    // Teacher-specific routes
     Route::prefix('teacher')->middleware('role:teacher')->group(function () {
         Route::get('/stats', [ProfileController::class, 'teacherStats']);
         Route::get('/recent-submissions', [ProfileController::class, 'recentSubmissions']);
         Route::get('/schedule', [ProfileController::class, 'todaySchedule']);
         Route::get('/classes', [ProfileController::class, 'teacherClasses']);
         Route::get('/students', [ProfileController::class, 'teacherStudents']);
         Route::post('/students/move-level', [ProfileController::class, 'moveStudentsLevel']);
         Route::post('/broadcast', [ProfileController::class, 'broadcastMessage']);
         Route::get('/analytics', [TeacherAnalyticsController::class, 'index']);
         
         // Teacher Oversight
         Route::get('/classes-overview', [TeacherController::class, 'getClassesOverview']);
         Route::get('/students-progress', [TeacherController::class, 'getStudentsProgress']);
         Route::get('/notifications', [TeacherController::class, 'getNotifications']);
         
         // Teacher Dashboard API endpoints
         Route::get('/dashboard', [MainTeacherController::class, 'getDashboardData']);
         Route::get('/dashboard/notifications', [MainTeacherController::class, 'getNotifications']);
         Route::post('/notifications/{notificationId}/read', [MainTeacherController::class, 'markNotificationAsRead']);
         Route::get('/student-progress', [MainTeacherController::class, 'getStudentProgress']);
         Route::get('/game-analytics', [MainTeacherController::class, 'getGameAnalytics']);
         Route::post('/analytics/update', [MainTeacherController::class, 'updateAnalytics']);
        Route::post('/submissions/{submission}/grade', [MainTeacherController::class, 'gradeSubmission']);
     });
});

/**
 * Public webhook endpoints (no authentication required)
 */
Route::prefix('webhooks')->group(function () {
    Route::post('/paystack', [PaymentController::class, 'paystackWebhook']);
});

// Include AI Feedback routes
require __DIR__.'/ai-feedback.php';

// Include Hasanat routes
require __DIR__.'/hasanat.php';

/**
 * File upload and media endpoints
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('uploads')->group(function () {
        Route::post('/audio', [SubmissionController::class, 'uploadAudio']);
        Route::post('/image', [AssignmentController::class, 'uploadImage']);
        Route::delete('/file/{file}', [AssignmentController::class, 'deleteFile']);
    });
    
    // Media signed URLs for S3 access
    Route::prefix('media')->group(function () {
        Route::get('/signed-get', [MediaController::class, 'signedGet']);
        Route::get('/signed-put', [MediaController::class, 'signedPut']);
    });
});