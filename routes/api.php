<?php

use App\Http\Controllers\API\ShipmentTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Shipment tracking routes
    Route::get('/shipment/track', [ShipmentTrackingController::class, 'trackByTrackingNumber']);
    Route::get('/shipment/{id}/track', [ShipmentTrackingController::class, 'trackByShipmentId']);
    Route::post('/shipment/{id}/sync', [ShipmentTrackingController::class, 'syncTracking']);
});