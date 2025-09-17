<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class RoleMiddleware
{
    /**
     * Handle an incoming request to check user roles.
     *
     * @param Request $request HTTP request
     * @param Closure $next Next middleware
     * @param string ...$roles Required roles (teacher, student, admin)
     * @return BaseResponse
     */
    public function handle(Request $request, Closure $next, string ...$roles): BaseResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            return response()->json([
                'message' => 'Insufficient permissions. Required roles: ' . implode(', ', $roles),
                'user_roles' => $user->getRoleNames()->toArray()
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}