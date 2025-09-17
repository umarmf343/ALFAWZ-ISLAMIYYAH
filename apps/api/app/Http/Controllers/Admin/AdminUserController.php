<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * AdminUserController manages user accounts and role assignments.
 * Provides search, pagination, role updates, and user impersonation.
 */
class AdminUserController extends Controller
{
    /**
     * Get paginated list of users with role information and search capability.
     * Supports filtering by name, email, and role.
     *
     * @param Request $request HTTP request with optional search and pagination params
     * @return \Illuminate\Http\JsonResponse paginated user list
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['roles:id,name'])
            ->select(['id', 'name', 'email', 'email_verified_at', 'last_login_at', 'created_at']);
        
        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Role filter
        if ($role = $request->get('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }
        
        // Status filter (active/inactive based on last login)
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('last_login_at', '>=', now()->subDays(30));
            } elseif ($status === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('last_login_at')
                      ->orWhere('last_login_at', '<', now()->subDays(30));
                });
            }
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $users = $query->paginate($request->get('per_page', 20));
        
        // Transform the data to include role names and status
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
                'roles' => $user->roles->pluck('name')->toArray(),
                'primary_role' => $user->roles->first()?->name ?? 'student',
                'last_login_at' => $user->last_login_at?->toISOString(),
                'created_at' => $user->created_at->toISOString(),
                'status' => $this->getUserStatus($user),
            ];
        });
        
        return response()->json($users);
    }
    
    /**
     * Update user role with audit logging.
     * Prevents self-demotion and validates role existence.
     *
     * @param int $id User ID
     * @param Request $request HTTP request with role data
     * @return \Illuminate\Http\JsonResponse success response
     */
    public function updateRole($id, Request $request)
    {
        $request->validate([
            'role' => ['required', 'string', Rule::in(['admin', 'teacher', 'student'])]
        ]);
        
        $user = User::findOrFail($id);
        $newRole = $request->get('role');
        $currentUser = auth()->user();
        
        // Prevent self-demotion from admin
        if ($user->id === $currentUser->id && $newRole !== 'admin') {
            return response()->json([
                'error' => 'Cannot demote yourself from admin role'
            ], 403);
        }
        
        $oldRoles = $user->roles->pluck('name')->toArray();
        
        // Update the role
        $user->syncRoles([$newRole]);
        
        // Log the audit trail
        $this->logAudit('user.role.update', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'old_roles' => $oldRoles,
            'new_role' => $newRole,
            'admin_id' => $currentUser->id,
            'admin_name' => $currentUser->name,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "User role updated to {$newRole}",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $newRole,
            ]
        ]);
    }
    
    /**
     * Bulk update user roles for multiple users.
     * Validates user IDs and role, then applies the role change with audit logging.
     * @param Request $request HTTP request with ids array and role string
     * @return \Illuminate\Http\JsonResponse Success response with count
     */
    public function bulkRole(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
            'role' => ['required', 'string', Rule::in(['admin', 'teacher', 'student'])]
        ]);
        
        $updatedCount = 0;
        $errors = [];
        $currentUser = auth()->user();
        
        foreach ($data['ids'] as $id) {
            try {
                $user = User::find($id);
                if ($user) {
                    // Prevent self-demotion from admin
                    if ($user->id === $currentUser->id && $data['role'] !== 'admin') {
                        $errors[] = "Cannot demote yourself from admin role (User ID: {$id})";
                        continue;
                    }
                    
                    $oldRoles = $user->roles->pluck('name')->toArray();
                    
                    // Remove all existing roles and assign the new one
                    $user->syncRoles([$data['role']]);
                    $updatedCount++;
                    
                    // Log individual user role change
                    $this->logAudit('user.bulk.role.individual', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'old_roles' => $oldRoles,
                        'new_role' => $data['role'],
                        'bulk_operation_id' => uniqid('bulk_', true)
                    ]);
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to update user {$id}: " . $e->getMessage();
                \Log::error("Bulk role update failed for user {$id}", [
                    'error' => $e->getMessage(),
                    'role' => $data['role']
                ]);
            }
        }
        
        // Audit log the bulk operation summary
        $this->logAudit('user.bulk.role', [
            'ids' => $data['ids'],
            'role' => $data['role'],
            'updated_count' => $updatedCount,
            'total_requested' => count($data['ids']),
            'errors_count' => count($errors),
            'admin_id' => $currentUser->id,
            'admin_name' => $currentUser->name
        ]);
        
        return response()->json([
            'ok' => true,
            'updated_count' => $updatedCount,
            'total_requested' => count($data['ids']),
            'errors' => $errors,
            'success_rate' => count($data['ids']) > 0 ? round(($updatedCount / count($data['ids'])) * 100, 2) : 0
        ]);
    }
    
    /**
     * Generate impersonation token for admin to log in as user.
     * Creates audit log and returns temporary access token.
     *
     * @param int $id User ID to impersonate
     * @return \Illuminate\Http\JsonResponse impersonation token
     */
    public function impersonate($id)
    {
        $user = User::findOrFail($id);
        $currentUser = auth()->user();
        
        // Prevent impersonating other admins
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Cannot impersonate admin users'
            ], 403);
        }
        
        // Generate a temporary token for impersonation
        $token = $user->createToken('impersonation', ['*'], now()->addHours(2))->plainTextToken;
        
        // Log the impersonation
        $this->logAudit('user.impersonate', [
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'admin_id' => $currentUser->id,
            'admin_name' => $currentUser->name,
            'expires_at' => now()->addHours(2)->toISOString(),
        ]);
        
        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'expires_at' => now()->addHours(2)->toISOString(),
        ]);
    }
    
    /**
     * Determine user status based on activity.
     * @param User $user User model
     * @return string active|inactive|new
     */
    private function getUserStatus(User $user): string
    {
        if (!$user->last_login_at) {
            return 'new';
        }
        
        if ($user->last_login_at >= now()->subDays(30)) {
            return 'active';
        }
        
        return 'inactive';
    }
    
    /**
     * Log admin audit trail.
     * @param string $action Action performed
     * @param array $meta Additional metadata
     */
    private function logAudit(string $action, array $meta = []): void
    {
        \DB::table('admin_audit_logs')->insert([
            'actor_id' => auth()->id(),
            'action' => $action,
            'entity_type' => User::class,
            'entity_id' => $meta['user_id'] ?? null,
            'meta_json' => json_encode($meta),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}