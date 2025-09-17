<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use App\Http\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services here if needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Sanctum
        Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);
        
        // Register custom middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('role', RoleMiddleware::class);
        
        // Configure API route model binding
        Route::bind('class', function ($value) {
            return \App\Models\ClassModel::findOrFail($value);
        });
        
        Route::bind('assignment', function ($value) {
            return \App\Models\Assignment::findOrFail($value);
        });
        
        Route::bind('hotspot', function ($value) {
            return \App\Models\Hotspot::findOrFail($value);
        });
        
        Route::bind('submission', function ($value) {
            return \App\Models\Submission::findOrFail($value);
        });
        
        Route::bind('feedback', function ($value) {
            return \App\Models\Feedback::findOrFail($value);
        });
        
        Route::bind('payment', function ($value) {
            return \App\Models\Payment::findOrFail($value);
        });
    }
}
