<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Core\AIFeedbackController;

/**
 * AI Feedback routes for Whisper integration.
 * Handles audio analysis and feedback generation.
 */
Route::middleware('auth:sanctum')->group(function () {
    // Generate AI feedback for submission
    Route::post('/submissions/ai-feedback', [AIFeedbackController::class, 'generateFeedback']);
    
    // Get AI feedback history for a submission
    Route::get('/submissions/{submission}/ai-feedback', [AIFeedbackController::class, 'getFeedbackHistory']);
    
    // Regenerate AI feedback with different parameters
    Route::post('/submissions/{submission}/ai-feedback/regenerate', [AIFeedbackController::class, 'regenerateFeedback']);
    
    // Get AI analysis capabilities and supported features
    Route::get('/ai-feedback/capabilities', [AIFeedbackController::class, 'getCapabilities']);
});