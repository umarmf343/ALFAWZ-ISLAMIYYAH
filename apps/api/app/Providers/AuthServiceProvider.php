<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\AdminPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        User::class => AdminPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     * Defines Gates for role-based access control.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define admin gate - user must have 'admin' role
        Gate::define('admin', function (User $user) {
            return $user->hasRole('admin');
        });

        // Define teacher gate - user must have 'teacher' or 'admin' role
        Gate::define('teacher', function (User $user) {
            return $user->hasRole('teacher') || $user->hasRole('admin');
        });

        // Define student gate - user must have 'student', 'teacher', or 'admin' role
        Gate::define('student', function (User $user) {
            return $user->hasRole('student') || $user->hasRole('teacher') || $user->hasRole('admin');
        });

        // Define super admin gate for critical operations
        Gate::define('super-admin', function (User $user) {
            return $user->hasRole('admin') && $user->email === config('app.super_admin_email');
        });

        // Define teacher-or-admin gate for routes that need either role
        Gate::define('teacher-or-admin', function (User $user) {
            return $user->hasRole('teacher') || $user->hasRole('admin');
        });
    }
}