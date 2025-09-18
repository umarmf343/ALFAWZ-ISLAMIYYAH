<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'level' => $validated['level'] ?? 1,
        ]);

        // Assign role using Spatie Permission
        $user->assignRole($validated['role']);

        $tokenPayload = $this->issueTokenFor($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user->makeHidden(['password']),
            'token' => $tokenPayload['token'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'refresh_expires_at' => $tokenPayload['refresh_expires_at'],
        ], 201);
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

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Revoke existing tokens for security
        $user->tokens()->delete();

        $tokenPayload = $this->issueTokenFor($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->makeHidden(['password']),
            'token' => $tokenPayload['token'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'refresh_expires_at' => $tokenPayload['refresh_expires_at'],
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Issue a fresh access token for the user with rotation metadata.
     */
    protected function issueTokenFor(User $user): array
    {
        $accessTokenExpiry = $this->accessTokenExpiry();
        $refreshExpiry = $this->refreshExpiry();

        $newToken = $user->createToken('auth-token', ['*'], $accessTokenExpiry);

        return [
            'token' => $newToken->plainTextToken,
            'token_expires_at' => $accessTokenExpiry?->toISOString(),
            'refresh_expires_at' => $refreshExpiry->toISOString(),
            'token_model' => $newToken->accessToken,
        ];
    }

    /**
     * Refresh an access token using the current bearer token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $incomingToken = $request->bearerToken();

        if (!$incomingToken) {
            Log::warning('auth.refresh.missing_token', ['ip' => $request->ip()]);

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $storedToken = PersonalAccessToken::findToken($incomingToken);

        if (!$storedToken || $storedToken->tokenable_type !== User::class) {
            Log::warning('auth.refresh.token_not_found', ['ip' => $request->ip()]);

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /** @var User $user */
        $user = $storedToken->tokenable;
        $refreshExpiry = $this->refreshExpiryForToken($storedToken);

        if (Carbon::now()->greaterThan($refreshExpiry)) {
            Log::warning('auth.refresh.expired', [
                'user_id' => $user->id,
                'token_id' => $storedToken->id,
            ]);

            $storedToken->delete();

            return response()->json([
                'message' => 'Refresh window has expired. Please login again.',
            ], 401);
        }

        $storedToken->delete();

        $tokenPayload = $this->issueTokenFor($user);

        Log::info('auth.refresh.success', [
            'user_id' => $user->id,
            'old_token_id' => $storedToken->id,
            'new_token_id' => $tokenPayload['token_model']->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'user' => $user->makeHidden(['password']),
            'token' => $tokenPayload['token'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'refresh_expires_at' => $tokenPayload['refresh_expires_at'],
        ]);
    }

    /**
     * Determine when an access token should expire.
     */
    protected function accessTokenExpiry(): ?Carbon
    {
        $ttl = config('sanctum.access_token_ttl');

        if ($ttl === null) {
            $ttl = config('sanctum.expiration');
        }

        if (!$ttl) {
            return null;
        }

        return Carbon::now()->addMinutes((int) $ttl);
    }

    /**
     * Determine when a refresh window should expire.
     */
    protected function refreshExpiry(): Carbon
    {
        return Carbon::now()->addMinutes($this->refreshTtlMinutes());
    }

    /**
     * Calculate refresh expiry for an existing token instance.
     */
    protected function refreshExpiryForToken(PersonalAccessToken $token): Carbon
    {
        $createdAt = $token->created_at instanceof Carbon
            ? $token->created_at
            : Carbon::parse($token->created_at ?? Carbon::now());

        return $createdAt->copy()->addMinutes($this->refreshTtlMinutes());
    }

    /**
     * Refresh window length in minutes.
     */
    protected function refreshTtlMinutes(): int
    {
        return (int) config('sanctum.refresh_ttl', 60 * 24 * 7);
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

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->makeHidden(['password']),
        ]);
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

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->makeHidden(['password']),
        ]);
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

        return response()->json([
            'message' => 'Password changed successfully. Please login again.',
        ]);
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
        
        return response()->json([
            'is_admin' => $user->hasRole('admin'),
            'roles' => $user->getRoleNames(),
        ]);
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
        
        return response()->json([
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
        ]);
    }
}