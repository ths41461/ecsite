<?php

use Inertia\Inertia;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CartController;
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

// Wishlist
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist', [WishlistController::class, 'store']);
Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy']);

// CART
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

// Mutations are rate-limited (30 req/min by default). Adjust as needed.
Route::middleware('throttle:cart-mutations')->group(function () {
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{line}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{line}', [CartController::class, 'destroy'])->name('cart.destroy');
});



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
