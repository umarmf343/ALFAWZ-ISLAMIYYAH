<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrgSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AdminOrgController manages organization-wide settings and configurations.
 * Provides centralized control over site-wide defaults and feature toggles.
 */
class AdminOrgController extends Controller
{
    /**
     * Get current organization settings.
     * Returns the single row of org-wide configuration values.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse organization settings
     */
    public function show(Request $request)
    {
        try {
            $orgSettings = OrgSetting::first();
            
            if (!$orgSettings) {
                // Create default settings if none exist
                $orgSettings = OrgSetting::create([
                    'tajweed_default' => true,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $orgSettings,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch org settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch organization settings',
            ], 500);
        }
    }
    
    /**
     * Update organization settings.
     * Modifies the site-wide configuration values.
     *
     * @param Request $request HTTP request with settings data
     * @return \Illuminate\Http\JsonResponse updated settings
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'tajweed_default' => 'required|boolean',
            ]);
            
            $orgSettings = OrgSetting::first();
            
            if (!$orgSettings) {
                // Create settings if none exist
                $orgSettings = OrgSetting::create($validated);
            } else {
                // Update existing settings
                $orgSettings->update($validated);
            }
            
            Log::info('Organization settings updated', [
                'admin_id' => auth()->id(),
                'settings' => $validated,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Organization settings updated successfully',
                'data' => $orgSettings->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update org settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update organization settings',
            ], 500);
        }
    }
    
    /**
     * Get organization settings summary for dashboard.
     * Returns key metrics and current configuration status.
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse settings summary
     */
    public function summary(Request $request)
    {
        try {
            $orgSettings = OrgSetting::first();
            
            if (!$orgSettings) {
                $orgSettings = OrgSetting::create(['tajweed_default' => true]);
            }
            
            // Get user preference statistics
            $userStats = \App\Models\User::selectRaw('
                COUNT(*) as total_users,
                SUM(CASE WHEN JSON_EXTRACT(settings, "$.tajweed_enabled") = true THEN 1 ELSE 0 END) as users_enabled,
                SUM(CASE WHEN JSON_EXTRACT(settings, "$.tajweed_enabled") = false THEN 1 ELSE 0 END) as users_disabled,
                SUM(CASE WHEN JSON_EXTRACT(settings, "$.tajweed_enabled") IS NULL THEN 1 ELSE 0 END) as users_default
            ')->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'org_settings' => $orgSettings,
                    'user_statistics' => [
                        'total_users' => $userStats->total_users ?? 0,
                        'users_with_enabled' => $userStats->users_enabled ?? 0,
                        'users_with_disabled' => $userStats->users_disabled ?? 0,
                        'users_using_default' => $userStats->users_default ?? 0,
                    ],
                    'effective_default' => $orgSettings->tajweed_default,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch org settings summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings summary',
            ], 500);
        }
    }
}