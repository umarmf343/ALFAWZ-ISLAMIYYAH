<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Core\HasanatController;

/**
 * Hasanat and gamification routes
 * All routes require authentication via Sanctum
 */
Route::middleware('auth:sanctum')->group(function () {
    // Get user's hasanat progress and statistics
    Route::get('/hasanat/progress', [HasanatController::class, 'getProgress']);
    
    // Get user's achievements and badges
    Route::get('/hasanat/achievements', [HasanatController::class, 'getAchievements']);
    
    // Get hasanat activity history
    Route::get('/hasanat/history', [HasanatController::class, 'getHistory']);
    
    // Award hasanat for specific activity (internal use)
    Route::post('/hasanat/award', [HasanatController::class, 'awardHasanat']);
    
    // Get leaderboard with hasanat rankings
    Route::get('/hasanat/leaderboard', [HasanatController::class, 'getLeaderboard']);
    
    // Get available rewards and their costs
    Route::get('/hasanat/rewards', [HasanatController::class, 'getRewards']);
    
    // Redeem hasanat for rewards
    Route::post('/hasanat/redeem', [HasanatController::class, 'redeemReward']);
    
    // Get hasanat calculation capabilities
    Route::get('/hasanat/capabilities', [HasanatController::class, 'getCapabilities']);
});