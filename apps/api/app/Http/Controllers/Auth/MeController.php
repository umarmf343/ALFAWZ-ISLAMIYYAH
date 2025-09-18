<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Auth;

use App\DataTransferObjects\Shared\ApiResponse;
use App\DataTransferObjects\Shared\UserData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Returns the authenticated user information or 401 if unauthenticated.
 * Includes proper cache headers to prevent stale auth state.
 */
class MeController extends Controller
{
    /**
     * Get the authenticated user's information.
     * 
     * @param Request $request The HTTP request instance
     * @return \Illuminate\Http\JsonResponse JSON response with user data or error
     */
    public function __invoke(Request $request)
    {
        $user = $request->user(); // Sanctum / token guard
        
        if (!$user) {
            return response()->json(ApiResponse::error('Unauthenticated'), 401)
                ->header('Cache-Control', 'no-store, private')
                ->header('Vary', 'Cookie');
        }

        return response()->json(
            ApiResponse::data([
                'user' => UserData::fromModel($user),
            ])
        )
            ->header('Cache-Control', 'no-store, private')
            ->header('Vary', 'Cookie');
    }
}