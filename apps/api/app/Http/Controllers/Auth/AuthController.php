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
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

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
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'level' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $defaultRole = config('auth.registration_default_role', 'student');
        $guardName = config('auth.defaults.guard', 'web');

        Role::findOrCreate($defaultRole, $guardName);

        $payload = RegisterRequestData::fromArray($validated, $defaultRole);

        $user = User::create(array_merge(
            $payload->toUserAttributes(),
            ['password' => Hash::make($payload->password)],
        ));

        // Assign role using Spatie Permission
        $user->assignRole($payload->role);

        event(new Registered($user));

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
     * Get authenticated user details including roles and permissions.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($user),
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ], 'Authenticated user retrieved successfully')
        );
    }

    /**
     * Refresh the current user's API token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->delete();
        }

        $newToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($user),
                'token' => $newToken,
                'roles' => $user->getRoleNames()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ], 'Token refreshed successfully')
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
     * Send a password reset link to the user's email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $status = PasswordFacade::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status === PasswordFacade::RESET_LINK_SENT) {
            return response()->json(
                ApiResponse::message(__($status))
            );
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Handle password reset submissions.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = PasswordFacade::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user) use ($validated): void {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === PasswordFacade::PASSWORD_RESET) {
            return response()->json(
                ApiResponse::message(__($status))
            );
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
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
     * Verify the authenticated user's email address.
     */
    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        if (!$request->hasValidSignature()) {
            return response()->json(
                ApiResponse::message('Invalid or expired verification link.', false),
                403
            );
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(
                ApiResponse::message('Email already verified.')
            );
        }

        $request->fulfill();

        return response()->json(
            ApiResponse::message('Email verified successfully.')
        );
    }

    /**
     * Resend the verification email to the authenticated user.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(
                ApiResponse::message('Email already verified.')
            );
        }

        $user->sendEmailVerificationNotification();

        return response()->json(
            ApiResponse::message('Verification email sent successfully.')
        );
    }

    /**
     * Check the verification status of the authenticated user.
     */
    public function checkEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(
            ApiResponse::data([
                'verified' => $user->hasVerifiedEmail(),
                'verified_at' => optional($user->email_verified_at)?->toISOString(),
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
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
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