<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Classes;
use App\Models\Payment;
use App\Models\AnalyticsSnapshot;
use App\Models\FlaggedContent;
use App\Models\QuranProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    /**
     * Get paginated list of users with filtering options.
     * Supports filtering by role, country, and activity status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        
        $query = User::with('roles');
        
        // Filter by role
        if ($request->role) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }
        
        // Filter by country (from preferences JSON)
        if ($request->country) {
            $query->whereJsonContains('preferences->country', $request->country);
        }
        
        // Filter by activity (active users in last 30 days)
        if ($request->active === 'true') {
            $query->where('last_login_at', '>=', now()->subDays(30));
        }
        
        // Search by name or email
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json($users);
    }

    /**
     * Update user details including role and status.
     * Supports role assignment and user suspension.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:admin,teacher,student',
            'status' => 'sometimes|in:active,suspended',
            'preferences' => 'sometimes|array'
        ]);
        
        // Update basic user info
        $user->update(array_intersect_key($validated, array_flip(['name', 'preferences'])));
        
        // Update role if provided
        if (isset($validated['role'])) {
            $user->syncRoles($validated['role']);
        }
        
        // Handle suspension (you might want to add a status column to users table)
        if (isset($validated['status']) && $validated['status'] === 'suspended') {
            // Revoke all tokens for suspended users
            $user->tokens()->delete();
        }
        
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Get paginated list of classes with teacher and member details.
     * Supports filtering and searching.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getClasses(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Classes::class);
        
        $query = Classes::with(['teacher', 'members']);
        
        // Filter by level
        if ($request->level) {
            $query->where('level', $request->level);
        }
        
        // Search by title
        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $classes = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json($classes);
    }

    /**
     * Assign or reassign teacher to a class.
     *
     * @param Request $request
     * @param Classes $class
     * @return JsonResponse
     */
    public function assignTeacher(Request $request, Classes $class): JsonResponse
    {
        $this->authorize('update', $class);
        
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id'
        ]);
        
        // Verify the user has teacher role
        $teacher = User::findOrFail($validated['teacher_id']);
        if (!$teacher->hasRole('teacher')) {
            return response()->json(['message' => 'Selected user is not a teacher'], 422);
        }
        
        $class->update(['teacher_id' => $validated['teacher_id']]);
        
        return response()->json([
            'message' => 'Teacher assigned successfully',
            'class' => $class->load('teacher')
        ]);
    }

    /**
     * Get analytics data for admin dashboard.
     * Returns cached analytics snapshots with fallback to real-time calculation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $this->authorize('view', AnalyticsSnapshot::class);
        
        $scope = $request->scope ?? 'global';
        $period = $request->period ?? 'weekly';
        
        // Try to get cached analytics first
        $analytics = AnalyticsSnapshot::getLatest($scope, $period);
        
        // If no cached data, generate real-time analytics
        if (!$analytics) {
            $data = $this->generateRealTimeAnalytics($scope, $period);
            return response()->json([
                'scope' => $scope,
                'period' => $period,
                'data_json' => $data,
                'generated_at' => now(),
                'is_cached' => false
            ]);
        }
        
        return response()->json($analytics);
    }

    /**
     * Generate real-time analytics data.
     *
     * @param string $scope
     * @param string $period
     * @return array
     */
    private function generateRealTimeAnalytics(string $scope, string $period): array
    {
        $timeframe = match($period) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subWeek()
        };
        
        return [
            'active_users' => User::where('last_login_at', '>=', $timeframe)->count(),
            'total_users' => User::count(),
            'new_users' => User::where('created_at', '>=', $timeframe)->count(),
            'verses_read' => QuranProgress::where('updated_at', '>=', $timeframe)
                                        ->sum('recited_count'),
            'hasanat_total' => User::sum('hasanat_total'),
            'total_classes' => Classes::count(),
            'active_classes' => Classes::whereHas('members')->count(),
            'total_payments' => Payment::where('status', 'completed')->sum('amount'),
            'recent_payments' => Payment::where('created_at', '>=', $timeframe)
                                       ->where('status', 'completed')
                                       ->sum('amount')
        ];
    }

    /**
     * Get flagged content for moderation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFlaggedContent(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FlaggedContent::class);
        
        $query = FlaggedContent::with(['content', 'flaggedBy']);
        
        // Filter by status
        if ($request->status) {
            $query->byStatus($request->status);
        } else {
            // Default to pending content
            $query->pending();
        }
        
        $flaggedContent = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json($flaggedContent);
    }

    /**
     * Resolve flagged content (mark as reviewed or removed).
     *
     * @param Request $request
     * @param FlaggedContent $flaggedContent
     * @return JsonResponse
     */
    public function resolveFlaggedContent(Request $request, FlaggedContent $flaggedContent): JsonResponse
    {
        $this->authorize('update', $flaggedContent);
        
        $validated = $request->validate([
            'status' => 'required|in:reviewed,removed',
            'admin_notes' => 'sometimes|string|max:1000'
        ]);
        
        if ($validated['status'] === 'reviewed') {
            $flaggedContent->markAsReviewed();
            $message = 'Content marked as reviewed';
        } else {
            $flaggedContent->markAsRemoved();
            $message = 'Content removed successfully';
        }
        
        return response()->json([
            'message' => $message,
            'flagged_content' => $flaggedContent
        ]);
    }

    /**
     * Get payments with filtering and search capabilities.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPayments(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        
        $query = Payment::with('user');
        
        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by currency
        if ($request->currency) {
            $query->where('currency', $request->currency);
        }
        
        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json($payments);
    }

    /**
     * Process payment refund via Paystack.
     *
     * @param Request $request
     * @param Payment $payment
     * @return JsonResponse
     */
    public function refundPayment(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);
        
        if ($payment->status !== 'completed') {
            return response()->json(['message' => 'Only completed payments can be refunded'], 422);
        }
        
        try {
            $client = new Client();
            $response = $client->post('https://api.paystack.co/refund', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'transaction' => $payment->paystack_ref,
                    'amount' => $payment->amount * 100 // Convert to kobo
                ]
            ]);
            
            $responseData = json_decode($response->getBody(), true);
            
            if ($responseData['status']) {
                $payment->update(['status' => 'refunded']);
                
                return response()->json([
                    'message' => 'Payment refunded successfully',
                    'payment' => $payment
                ]);
            }
            
            return response()->json(['message' => 'Refund failed'], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Refund processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard overview statistics.
     *
     * @return JsonResponse
     */
    public function getDashboardOverview(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        
        $overview = Cache::remember('admin_dashboard_overview', 300, function () {
            return [
                'total_users' => User::count(),
                'active_users_today' => User::where('last_login_at', '>=', now()->subDay())->count(),
                'total_teachers' => User::role('teacher')->count(),
                'total_students' => User::role('student')->count(),
                'total_classes' => Classes::count(),
                'active_classes' => Classes::whereHas('members')->count(),
                'pending_flags' => FlaggedContent::pending()->count(),
                'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
                'monthly_revenue' => Payment::where('status', 'completed')
                                          ->where('created_at', '>=', now()->subMonth())
                                          ->sum('amount')
            ];
        });
        
        return response()->json($overview);
    }
}