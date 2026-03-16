<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\CouponController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    // Redirect user settings to dashboard
    Route::redirect('settings', '/dashboard')->name('settings');
    Route::redirect('settings/profile', '/dashboard?tab=profile')->name('profile.edit');
    Route::redirect('settings/password', '/dashboard?tab=profile')->name('password.edit');
    
    // Keep the actual update routes for form submissions
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance settings can remain separate or also redirect to dashboard
    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    // Admin-only routes for coupon management (these should stay separate from user dashboard)
    Route::get('settings/coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::post('settings/coupons', [CouponController::class, 'store'])->name('coupons.store');
    Route::put('settings/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
    Route::delete('settings/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
});
