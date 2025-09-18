<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Auth;

use App\DataTransferObjects\Auth\LoginRequestData;
use App\DataTransferObjects\Auth\RegisterRequestData;
use App\DataTransferObjects\Shared\ApiResponse;
use App\DataTransferObjects\Shared\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user account.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:student,teacher,admin'],
            'phone' => ['nullable', 'string', 'max:20'],
            'level' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $payload = RegisterRequestData::fromArray($validated);

        $user = User::create(array_merge(
            $payload->toUserAttributes(),
            ['password' => Hash::make($payload->password)],
        ));

        // Assign role using Spatie Permission
        $user->assignRole($payload->role);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($user),
                'token' => $token,
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ], 'User registered successfully'),
            201
        );
    }

    /**
     * Authenticate user and return token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $payload = LoginRequestData::fromArray($validated);

        $user = User::where('email', $payload->email)->first();

        if (!$user || !Hash::check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Revoke existing tokens for security
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($user),
                'token' => $token,
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ], 'Login successful')
        );
    }

    /**
     * Logout user and revoke token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(ApiResponse::message('Logout successful'));
    }

    /**
     * Get authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($request->user()),
            ])
        );
    }

    /**
     * Update user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:3'],
        ]);

        $user->update($validated);

        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($user, includeAccess: false),
            ], 'Profile updated successfully')
        );
    }

    /**
     * Change user password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke all tokens to force re-login
        $user->tokens()->delete();

        return response()->json(
            ApiResponse::message('Password changed successfully. Please login again.')
        );
    }

    /**
     * Check if user has admin role.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAdminRole(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json(
            ApiResponse::data([
                'is_admin' => $user->hasRole('admin'),
                'roles' => $user->getRoleNames()->toArray(),
            ])
        );
    }

    /**
     * Get user permissions and roles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json(
            ApiResponse::data([
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
            ])
        );
    }
}