<?php

use App\Http\Controllers\API\AIRecommendationController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\ShipmentTrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

RateLimiter::for('ai-api', function (Request $request) {
    $key = $request->hasSession() ? $request->session()->getId() : $request->ip();

    return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($key);
});

Route::prefix('v1')->group(function () {
    // Shipment tracking routes
    Route::get('/shipment/track', [ShipmentTrackingController::class, 'trackByTrackingNumber']);
    Route::get('/shipment/{id}/track', [ShipmentTrackingController::class, 'trackByShipmentId']);
    Route::post('/shipment/{id}/sync', [ShipmentTrackingController::class, 'syncTracking']);

    // AI recommendation routes (rate limited: 20 req/min)
    Route::middleware('throttle:ai-api')->group(function () {
        Route::post('/ai/quiz', [AIRecommendationController::class, 'submitQuiz']);
        Route::get('/ai/recommendations', [AIRecommendationController::class, 'getRecommendations']);
        Route::post('/ai/chat', [ChatController::class, 'sendMessage']);
    });
});
