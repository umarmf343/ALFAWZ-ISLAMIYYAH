<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * AdminFlagsController manages feature flags and kill switches.
 * Provides centralized control over feature rollouts and system toggles.
 */
class AdminFlagsController extends Controller
{
    /**
     * Get all feature flags with usage statistics.
     * Returns organized flags for easy management and monitoring.
     *
     * @param Request $request HTTP request with optional filters
     * @return \Illuminate\Http\JsonResponse feature flags list
     */
    public function index(Request $request)
    {
        $query = DB::table('feature_flags')
            ->select([
                'id',
                'key',
                'enabled',
                'segment',
                'note',
                'created_at',
                'updated_at'
            ]);
        
        // Filter by enabled status
        if ($request->has('enabled')) {
            $enabled = $request->boolean('enabled');
            $query->where('enabled', $enabled);
        }
        
        // Search by key or note
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('segment', 'like', "%{$search}%");
            });
        }
        
        // Filter by segment
        if ($segment = $request->get('segment')) {
            $query->where('segment', $segment);
        }
        
        $flags = $query->orderBy('key')->get();
        
        // Transform data and add usage statistics
        $transformedFlags = $flags->map(function ($flag) {
            return [
                'id' => $flag->id,
                'key' => $flag->key,
                'enabled' => (bool) $flag->enabled,
                'segment' => $flag->segment,
                'note' => $flag->note,
                'category' => $this->getCategoryFromKey($flag->key),
                'usage_stats' => $this->getUsageStats($flag->key),
                'created_at' => $flag->created_at,
                'updated_at' => $flag->updated_at,
            ];
        });
        
        // Group by category
        $groupedFlags = $transformedFlags->groupBy('category');
        
        // Summary statistics
        $summary = [
            'total_flags' => $flags->count(),
            'enabled_flags' => $flags->where('enabled', true)->count(),
            'disabled_flags' => $flags->where('enabled', false)->count(),
            'categories' => $groupedFlags->keys()->toArray(),
        ];
        
        return response()->json([
            'flags' => $groupedFlags,
            'summary' => $summary,
        ]);
    }
    
    /**
     * Get a specific feature flag by key.
     * Returns detailed flag information with usage history.
     *
     * @param Request $request HTTP request
     * @param string $key Feature flag key
     * @return \Illuminate\Http\JsonResponse flag details
     */
    public function show(Request $request, string $key)
    {
        $flag = DB::table('feature_flags')->where('key', $key)->first();
        
        if (!$flag) {
            return response()->json([
                'error' => 'Feature flag not found',
                'key' => $key,
            ], 404);
        }
        
        // Get recent audit logs for this flag
        $auditLogs = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.actor_id', '=', 'users.id')
            ->where('admin_audit_logs.entity_type', 'feature_flag')
            ->where('admin_audit_logs.entity_id', $key)
            ->select([
                'admin_audit_logs.action',
                'admin_audit_logs.meta_json',
                'admin_audit_logs.created_at',
                'users.name as actor_name',
                'users.email as actor_email'
            ])
            ->orderByDesc('admin_audit_logs.created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                $meta = json_decode($log->meta_json, true) ?? [];
                return [
                    'action' => $log->action,
                    'actor' => [
                        'name' => $log->actor_name,
                        'email' => $log->actor_email,
                    ],
                    'meta' => $meta,
                    'created_at' => $log->created_at,
                ];
            });
        
        return response()->json([
            'id' => $flag->id,
            'key' => $flag->key,
            'enabled' => (bool) $flag->enabled,
            'segment' => $flag->segment,
            'note' => $flag->note,
            'category' => $this->getCategoryFromKey($flag->key),
            'usage_stats' => $this->getUsageStats($flag->key),
            'audit_history' => $auditLogs,
            'created_at' => $flag->created_at,
            'updated_at' => $flag->updated_at,
        ]);
    }
    
    /**
     * Create or update a feature flag.
     * Handles flag configuration with validation and audit logging.
     *
     * @param Request $request HTTP request with flag data
     * @param int|null $id Flag ID for updates
     * @return \Illuminate\Http\JsonResponse created/updated flag
     */
    public function store(Request $request, $id = null)
    {
        $request->validate([
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('feature_flags', 'key')->ignore($id)
            ],
            'enabled' => 'required|boolean',
            'segment' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);
        
        $flagData = [
            'key' => $request->get('key'),
            'enabled' => $request->boolean('enabled'),
            'segment' => $request->get('segment'),
            'note' => $request->get('note'),
            'updated_at' => now(),
        ];
        
        if ($id) {
            // Update existing flag
            $oldFlag = DB::table('feature_flags')->where('id', $id)->first();
            DB::table('feature_flags')->where('id', $id)->update($flagData);
            $flag = DB::table('feature_flags')->where('id', $id)->first();
            
            // Log the update
            $this->logFlagChange($flag->key, $flagData, 'updated', $oldFlag);
            $message = 'Feature flag updated successfully';
        } else {
            // Create new flag
            $flagData['created_at'] = now();
            $flagId = DB::table('feature_flags')->insertGetId($flagData);
            $flag = DB::table('feature_flags')->where('id', $flagId)->first();
            
            // Log the creation
            $this->logFlagChange($flag->key, $flagData, 'created');
            $message = 'Feature flag created successfully';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'flag' => [
                'id' => $flag->id,
                'key' => $flag->key,
                'enabled' => (bool) $flag->enabled,
                'segment' => $flag->segment,
                'note' => $flag->note,
                'category' => $this->getCategoryFromKey($flag->key),
            ],
        ], $id ? 200 : 201);
    }
    
    /**
     * Toggle a feature flag's enabled status.
     * Quick toggle with audit logging.
     *
     * @param Request $request HTTP request
     * @param string $key Feature flag key
     * @return \Illuminate\Http\JsonResponse toggle result
     */
    public function toggle(Request $request, string $key)
    {
        $flag = DB::table('feature_flags')->where('key', $key)->first();
        
        if (!$flag) {
            return response()->json([
                'error' => 'Feature flag not found',
                'key' => $key,
            ], 404);
        }
        
        $newEnabled = !$flag->enabled;
        
        DB::table('feature_flags')
            ->where('key', $key)
            ->update([
                'enabled' => $newEnabled,
                'updated_at' => now(),
            ]);
        
        // Log the toggle
        $this->logFlagChange($key, ['enabled' => $newEnabled], 'toggled', $flag);
        
        return response()->json([
            'success' => true,
            'message' => 'Feature flag toggled successfully',
            'key' => $key,
            'enabled' => $newEnabled,
        ]);
    }
    
    /**
     * Delete a feature flag.
     * Removes flag with safety checks and audit logging.
     *
     * @param Request $request HTTP request
     * @param string $key Feature flag key
     * @return \Illuminate\Http\JsonResponse deletion result
     */
    public function destroy(Request $request, string $key)
    {
        $flag = DB::table('feature_flags')->where('key', $key)->first();
        
        if (!$flag) {
            return response()->json([
                'error' => 'Feature flag not found',
                'key' => $key,
            ], 404);
        }
        
        // Prevent deletion of critical flags
        if ($this->isCriticalFlag($key)) {
            return response()->json([
                'error' => 'Cannot delete critical system flag',
                'key' => $key,
            ], 403);
        }
        
        DB::table('feature_flags')->where('key', $key)->delete();
        
        // Log the deletion
        $this->logFlagChange($key, null, 'deleted', $flag);
        
        return response()->json([
            'success' => true,
            'message' => 'Feature flag deleted successfully',
            'key' => $key,
        ]);
    }
    
    /**
     * Get feature flags template with common patterns.
     * Provides guidance for creating new flags.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse flags template
     */
    public function template(Request $request)
    {
        $template = [
            'authentication' => [
                'auth.registration_enabled' => [
                    'description' => 'Allow new user registrations',
                    'default_enabled' => true,
                    'segment' => null,
                ],
                'auth.social_login_enabled' => [
                    'description' => 'Enable social media login options',
                    'default_enabled' => false,
                    'segment' => null,
                ],
                'auth.two_factor_required' => [
                    'description' => 'Require two-factor authentication',
                    'default_enabled' => false,
                    'segment' => 'admin',
                ],
            ],
            'learning' => [
                'learning.whisper_enabled' => [
                    'description' => 'Enable AI-powered pronunciation feedback',
                    'default_enabled' => true,
                    'segment' => null,
                ],
                'learning.auto_grading_enabled' => [
                    'description' => 'Enable automatic assignment grading',
                    'default_enabled' => false,
                    'segment' => 'beta',
                ],
                'learning.hasanat_tracking_enabled' => [
                    'description' => 'Enable hasanat point tracking',
                    'default_enabled' => true,
                    'segment' => null,
                ],
            ],
            'payments' => [
                'payments.paystack_enabled' => [
                    'description' => 'Enable Paystack payment processing',
                    'default_enabled' => true,
                    'segment' => null,
                ],
                'payments.free_trial_enabled' => [
                    'description' => 'Allow free trial periods',
                    'default_enabled' => true,
                    'segment' => null,
                ],
            ],
            'ui' => [
                'ui.dark_mode_enabled' => [
                    'description' => 'Enable dark mode theme option',
                    'default_enabled' => false,
                    'segment' => 'beta',
                ],
                'ui.new_dashboard_enabled' => [
                    'description' => 'Enable redesigned dashboard',
                    'default_enabled' => false,
                    'segment' => 'alpha',
                ],
            ],
            'system' => [
                'system.maintenance_mode' => [
                    'description' => 'Enable maintenance mode (kill switch)',
                    'default_enabled' => false,
                    'segment' => null,
                ],
                'system.debug_mode' => [
                    'description' => 'Enable debug information display',
                    'default_enabled' => false,
                    'segment' => 'admin',
                ],
            ],
        ];
        
        return response()->json([
            'template' => $template,
            'categories' => array_keys($template),
            'segments' => ['alpha', 'beta', 'admin', 'teacher', 'student'],
        ]);
    }
    
    /**
     * Get category from flag key.
     * @param string $key Flag key
     * @return string category name
     */
    private function getCategoryFromKey(string $key): string
    {
        $parts = explode('.', $key);
        return $parts[0] ?? 'general';
    }
    
    /**
     * Get usage statistics for a flag (placeholder).
     * @param string $key Flag key
     * @return array usage statistics
     */
    private function getUsageStats(string $key): array
    {
        // This would typically track how often the flag is checked
        // For now, return placeholder data
        return [
            'checks_today' => 0,
            'checks_this_week' => 0,
            'last_checked_at' => null,
            'note' => 'Usage tracking not implemented',
        ];
    }
    
    /**
     * Check if a flag is critical and should not be deleted.
     * @param string $key Flag key
     * @return bool is critical
     */
    private function isCriticalFlag(string $key): bool
    {
        $criticalFlags = [
            'system.maintenance_mode',
            'auth.registration_enabled',
            'payments.paystack_enabled',
        ];
        
        return in_array($key, $criticalFlags);
    }
    
    /**
     * Log feature flag changes to audit log.
     * @param string $key Flag key
     * @param array|null $newData New flag data
     * @param string $action Action performed
     * @param object|null $oldFlag Previous flag state
     */
    private function logFlagChange(string $key, ?array $newData, string $action, ?object $oldFlag = null): void
    {
        $meta = [
            'key' => $key,
            'action' => $action,
        ];
        
        if ($newData) {
            $meta['new_data'] = $newData;
        }
        
        if ($oldFlag) {
            $meta['old_data'] = [
                'enabled' => (bool) $oldFlag->enabled,
                'segment' => $oldFlag->segment,
                'note' => $oldFlag->note,
            ];
        }
        
        DB::table('admin_audit_logs')->insert([
            'actor_id' => auth()->id(),
            'action' => "flag_{$action}",
            'entity_type' => 'feature_flag',
            'entity_id' => $key,
            'meta_json' => json_encode($meta),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}