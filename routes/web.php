<?php

use Inertia\Inertia;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/products/{product}', [ProductController::class, 'show'])
    ->name('products.show');

Route::post('/e/pdp-view', [EventController::class, 'pdpView'])->name('events.pdp_view');
Route::post('/e/add-to-cart', [EventController::class, 'addToCart'])->name('events.add_to_cart');
Route::post('/e/wishlist-add', [EventController::class, 'wishlistAdd'])->name('events.wishlist_add');

// Wishlist (session-based MVP)
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist', [WishlistController::class, 'store']);
Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
