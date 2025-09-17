<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * For API routes, return null to avoid redirect and return 401 JSON response instead.
     *
     * @param Request $request HTTP request instance
     * @return string|null redirect path or null for API routes
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API routes, don't redirect - let it return 401 JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // For web routes, return null since we don't have a login route
        return null;
    }
}