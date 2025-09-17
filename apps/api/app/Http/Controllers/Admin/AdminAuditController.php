<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * AdminAuditController manages audit logs and system activity tracking.
 * Provides comprehensive oversight of admin actions and system changes.
 */
class AdminAuditController extends Controller
{
    /**
     * Get paginated audit logs with filtering and search.
     * Provides comprehensive activity monitoring with detailed context.
     *
     * @param Request $request HTTP request with filtering parameters
     * @return \Illuminate\Http\JsonResponse paginated audit logs
     */
    public function index(Request $request)
    {
        $query = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->select([
                'admin_audit_logs.id',
                'admin_audit_logs.actor_id',
                'users.name as actor_name',
                'users.email as actor_email',
                'users.role as actor_role',
                'admin_audit_logs.action',
                'admin_audit_logs.entity_type',
                'admin_audit_logs.entity_id',
                'admin_audit_logs.meta_json',
                'admin_audit_logs.ip',
                'admin_audit_logs.user_agent',
                'admin_audit_logs.created_at'
            ]);
        
        // Search by actor name, email, action, or entity
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('admin_audit_logs.action', 'like', "%{$search}%")
                  ->orWhere('admin_audit_logs.entity_type', 'like', "%{$search}%")
                  ->orWhere('admin_audit_logs.entity_id', 'like', "%{$search}%")
                  ->orWhere('admin_audit_logs.ip', 'like', "%{$search}%");
            });
        }
        
        // Filter by actor
        if ($actorId = $request->get('actor_id')) {
            $query->where('admin_audit_logs.actor_id', $actorId);
        }
        
        // Filter by action
        if ($action = $request->get('action')) {
            $query->where('admin_audit_logs.action', $action);
        }
        
        // Filter by entity type
        if ($entityType = $request->get('entity_type')) {
            $query->where('admin_audit_logs.entity_type', $entityType);
        }
        
        // Filter by date range
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('admin_audit_logs.created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('admin_audit_logs.created_at', '<=', $endDate);
        }
        
        // Filter by IP address
        if ($ip = $request->get('ip')) {
            $query->where('admin_audit_logs.ip', $ip);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'actor_name') {
            $query->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy("admin_audit_logs.{$sortBy}", $sortOrder);
        }
        
        // Manual pagination
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $total = $query->count();
        $logs = $query->offset($offset)->limit($perPage)->get();
        
        // Transform data
        $transformedLogs = $logs->map(function ($log) {
            $meta = json_decode($log->meta_json, true) ?? [];
            
            return [
                'id' => $log->id,
                'actor' => [
                    'id' => $log->actor_id,
                    'name' => $log->actor_name,
                    'email' => $log->actor_email,
                    'role' => $log->actor_role,
                ],
                'action' => $log->action,
                'action_category' => $this->getActionCategory($log->action),
                'entity' => [
                    'type' => $log->entity_type,
                    'id' => $log->entity_id,
                ],
                'meta' => $meta,
                'context' => [
                    'ip' => $log->ip,
                    'user_agent' => $log->user_agent,
                    'browser' => $this->parseBrowser($log->user_agent),
                ],
                'created_at' => $log->created_at,
                'time_ago' => $this->timeAgo($log->created_at),
            ];
        });
        
        return response()->json([
            'data' => $transformedLogs,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }
    
    /**
     * Get detailed view of a specific audit log entry.
     * Provides comprehensive context and related activity.
     *
     * @param Request $request HTTP request
     * @param int $id Audit log ID
     * @return \Illuminate\Http\JsonResponse detailed audit log
     */
    public function show(Request $request, int $id)
    {
        $log = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->select([
                'admin_audit_logs.*',
                'users.name as actor_name',
                'users.email as actor_email',
                'users.role as actor_role'
            ])
            ->where('admin_audit_logs.id', $id)
            ->first();
        
        if (!$log) {
            return response()->json([
                'error' => 'Audit log not found',
                'id' => $id,
            ], 404);
        }
        
        $meta = json_decode($log->meta_json, true) ?? [];
        
        // Get related logs (same entity or same actor around the same time)
        $relatedLogs = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->select([
                'admin_audit_logs.id',
                'admin_audit_logs.action',
                'admin_audit_logs.entity_type',
                'admin_audit_logs.entity_id',
                'admin_audit_logs.created_at',
                'users.name as actor_name'
            ])
            ->where('admin_audit_logs.id', '!=', $id)
            ->where(function ($query) use ($log) {
                $query->where(function ($q) use ($log) {
                    // Same entity
                    $q->where('admin_audit_logs.entity_type', $log->entity_type)
                      ->where('admin_audit_logs.entity_id', $log->entity_id);
                })->orWhere(function ($q) use ($log) {
                    // Same actor within 1 hour
                    $q->where('admin_audit_logs.actor_id', $log->actor_id)
                      ->whereBetween('admin_audit_logs.created_at', [
                          Carbon::parse($log->created_at)->subHour(),
                          Carbon::parse($log->created_at)->addHour()
                      ]);
                });
            })
            ->orderByDesc('admin_audit_logs.created_at')
            ->limit(10)
            ->get()
            ->map(function ($relatedLog) {
                return [
                    'id' => $relatedLog->id,
                    'action' => $relatedLog->action,
                    'entity_type' => $relatedLog->entity_type,
                    'entity_id' => $relatedLog->entity_id,
                    'actor_name' => $relatedLog->actor_name,
                    'created_at' => $relatedLog->created_at,
                ];
            });
        
        return response()->json([
            'id' => $log->id,
            'actor' => [
                'id' => $log->actor_id,
                'name' => $log->actor_name,
                'email' => $log->actor_email,
                'role' => $log->actor_role,
            ],
            'action' => $log->action,
            'action_category' => $this->getActionCategory($log->action),
            'entity' => [
                'type' => $log->entity_type,
                'id' => $log->entity_id,
            ],
            'meta' => $meta,
            'context' => [
                'ip' => $log->ip,
                'user_agent' => $log->user_agent,
                'browser' => $this->parseBrowser($log->user_agent),
                'location' => $this->getLocationFromIp($log->ip),
            ],
            'related_logs' => $relatedLogs,
            'created_at' => $log->created_at,
            'time_ago' => $this->timeAgo($log->created_at),
        ]);
    }
    
    /**
     * Get audit analytics and activity summaries.
     * Provides insights into admin activity patterns and system usage.
     *
     * @param Request $request HTTP request with date filters
     * @return \Illuminate\Http\JsonResponse audit analytics
     */
    public function analytics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        // Activity summary
        $totalLogs = DB::table('admin_audit_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        $uniqueActors = DB::table('admin_audit_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('actor_id')
            ->count();
        
        // Activity by action type
        $actionBreakdown = DB::table('admin_audit_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'action' => $item->action,
                    'category' => $this->getActionCategory($item->action),
                    'count' => $item->count,
                ];
            });
        
        // Activity by entity type
        $entityBreakdown = DB::table('admin_audit_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('entity_type', DB::raw('COUNT(*) as count'))
            ->groupBy('entity_type')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->entity_type => $item->count];
            });
        
        // Most active admins
        $topActors = DB::table('admin_audit_logs')
            ->join('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->whereBetween('admin_audit_logs.created_at', [$startDate, $endDate])
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(*) as activity_count'),
                DB::raw('COUNT(DISTINCT DATE(admin_audit_logs.created_at)) as active_days')
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get()
            ->map(function ($actor) {
                return [
                    'user' => [
                        'id' => $actor->id,
                        'name' => $actor->name,
                        'email' => $actor->email,
                    ],
                    'activity_count' => $actor->activity_count,
                    'active_days' => $actor->active_days,
                ];
            });
        
        // Daily activity trend
        $dailyActivity = collect()
            ->range(0, Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate)))
            ->map(function ($daysFromStart) use ($startDate) {
                $date = Carbon::parse($startDate)->addDays($daysFromStart)->toDateString();
                
                $dayLogs = DB::table('admin_audit_logs')
                    ->whereDate('created_at', $date)
                    ->count();
                
                $dayActors = DB::table('admin_audit_logs')
                    ->whereDate('created_at', $date)
                    ->distinct('actor_id')
                    ->count();
                
                return [
                    'date' => $date,
                    'log_count' => $dayLogs,
                    'active_actors' => $dayActors,
                ];
            });
        
        // Security insights
        $securityInsights = $this->getSecurityInsights($startDate, $endDate);
        
        return response()->json([
            'summary' => [
                'total_logs' => $totalLogs,
                'unique_actors' => $uniqueActors,
                'avg_daily_activity' => round($totalLogs / max(1, Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate))), 1),
            ],
            'action_breakdown' => $actionBreakdown,
            'entity_breakdown' => $entityBreakdown,
            'top_actors' => $topActors,
            'daily_activity' => $dailyActivity,
            'security_insights' => $securityInsights,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    /**
     * Export audit logs to CSV format.
     * Provides downloadable audit trail for compliance.
     *
     * @param Request $request HTTP request with filters
     * @return \Illuminate\Http\Response CSV download
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        $logs = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->select([
                'admin_audit_logs.id',
                'admin_audit_logs.created_at',
                'users.name as actor_name',
                'users.email as actor_email',
                'admin_audit_logs.action',
                'admin_audit_logs.entity_type',
                'admin_audit_logs.entity_id',
                'admin_audit_logs.ip',
                'admin_audit_logs.meta_json'
            ])
            ->whereBetween('admin_audit_logs.created_at', [$startDate, $endDate])
            ->orderBy('admin_audit_logs.created_at', 'desc')
            ->get();
        
        $csv = "ID,Timestamp,Actor Name,Actor Email,Action,Entity Type,Entity ID,IP Address,Metadata\n";
        
        foreach ($logs as $log) {
            $meta = json_decode($log->meta_json, true) ?? [];
            $metaString = json_encode($meta, JSON_UNESCAPED_SLASHES);
            
            $csv .= implode(',', [
                $log->id,
                $log->created_at,
                '"' . str_replace('"', '""', $log->actor_name ?? '') . '"',
                '"' . str_replace('"', '""', $log->actor_email ?? '') . '"',
                '"' . str_replace('"', '""', $log->action) . '"',
                '"' . str_replace('"', '""', $log->entity_type) . '"',
                '"' . str_replace('"', '""', $log->entity_id) . '"',
                $log->ip,
                '"' . str_replace('"', '""', $metaString) . '"',
            ]) . "\n";
        }
        
        $filename = "audit_logs_{$startDate}_to_{$endDate}.csv";
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
    
    /**
     * Get action category from action name.
     * @param string $action Action name
     * @return string category
     */
    private function getActionCategory(string $action): string
    {
        $categories = [
            'user_' => 'User Management',
            'role_' => 'Role Management',
            'class_' => 'Class Management',
            'assignment_' => 'Assignment Management',
            'submission_' => 'Submission Management',
            'setting_' => 'Settings',
            'flag_' => 'Feature Flags',
            'payment_' => 'Billing',
            'impersonate_' => 'Security',
        ];
        
        foreach ($categories as $prefix => $category) {
            if (str_starts_with($action, $prefix)) {
                return $category;
            }
        }
        
        return 'Other';
    }
    
    /**
     * Parse browser information from user agent.
     * @param string|null $userAgent User agent string
     * @return string browser info
     */
    private function parseBrowser(?string $userAgent): string
    {
        if (!$userAgent) return 'Unknown';
        
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        
        return 'Other';
    }
    
    /**
     * Get location from IP address (placeholder).
     * @param string|null $ip IP address
     * @return string location info
     */
    private function getLocationFromIp(?string $ip): string
    {
        if (!$ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.')) {
            return 'Local/Private';
        }
        
        // This would typically use a geolocation service
        return 'Unknown Location';
    }
    
    /**
     * Get human-readable time ago.
     * @param string $timestamp Timestamp
     * @return string time ago
     */
    private function timeAgo(string $timestamp): string
    {
        return Carbon::parse($timestamp)->diffForHumans();
    }
    
    /**
     * Get security insights from audit logs.
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array security insights
     */
    private function getSecurityInsights(string $startDate, string $endDate): array
    {
        // Failed login attempts (if tracked)
        $suspiciousActivity = DB::table('admin_audit_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('action', 'like', '%failed%')
            ->count();
        
        // Multiple IP addresses per user
        $multiIpUsers = DB::table('admin_audit_logs')
            ->join('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->whereBetween('admin_audit_logs.created_at', [$startDate, $endDate])
            ->select('users.name', 'users.email', DB::raw('COUNT(DISTINCT admin_audit_logs.ip) as ip_count'))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->having('ip_count', '>', 1)
            ->orderByDesc('ip_count')
            ->limit(5)
            ->get();
        
        return [
            'suspicious_activity_count' => $suspiciousActivity,
            'multi_ip_users' => $multiIpUsers,
            'note' => 'Security insights are basic - consider implementing more comprehensive monitoring',
        ];
    }
}