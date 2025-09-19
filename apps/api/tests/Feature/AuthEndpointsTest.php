<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['student', 'teacher', 'admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_registration_assigns_student_role_by_default(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->hasRole('admin'));

        $response->assertJsonPath('data.roles', ['student']);
    }

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_updates_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'change@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $user->createToken('auth-token');

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_user_endpoint_returns_authenticated_user_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $token = $user->createToken('auth-token');

        $response = $this->withToken($token->plainTextToken)->getJson('/api/auth/user');

        $response->assertOk();
        $response->assertJsonPath('data.user.email', $user->email);
        $response->assertJsonPath('data.roles.0', 'student');
    }

    public function test_refresh_endpoint_replaces_current_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $originalToken = $user->createToken('auth-token');
        $originalTokenId = $originalToken->accessToken->id;

        $response = $this->withToken($originalToken->plainTextToken)->postJson('/api/auth/refresh');

        $response->assertOk();
        $newToken = $response->json('data.token');

        $this->assertNotEmpty($newToken);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $originalTokenId]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_email_verification_requires_signed_url(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $hash = sha1($user->getEmailForVerification());

        $response = $this->getJson("/api/auth/email/verify/{$user->getKey()}/{$hash}");

        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_signed_email_verification_marks_user_exactly_once(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $parsedUrl = parse_url($verificationUrl);
        $relativeUrl = $parsedUrl['path'] . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');

        $response = $this->getJson($relativeUrl);

        $response->assertOk();
        $response->assertJsonPath('message', 'Email verified successfully.');
        $verifiedAt = $user->fresh()->email_verified_at;
        $this->assertNotNull($verifiedAt);

        $secondResponse = $this->getJson($relativeUrl);

        $secondResponse->assertOk();
        $secondResponse->assertJsonPath('message', 'Email already verified.');
        $this->assertEquals($verifiedAt, $user->fresh()->email_verified_at);
    }

    public function test_resend_verification_email_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/email/resend');

        $response->assertOk();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_check_email_verification_returns_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/email/verification-status');

        $response->assertOk();
        $response->assertJsonPath('data.verified', true);
    }
}
