<?php

use Inertia\Inertia;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\OrdersController;
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
    // Coupons
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
});



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

// CHECKOUT
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/order', [CheckoutController::class, 'createOrder'])->name('checkout.order');
Route::get('/checkout/{orderNumber}/details', [CheckoutController::class, 'details'])->name('checkout.details');
Route::post('/checkout/{orderNumber}/details', [CheckoutController::class, 'updateDetails'])->name('checkout.details.update');
// Spec parity aliases
Route::post('/checkout/create', [CheckoutController::class, 'store'])->name('checkout.create');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/thanks/{orderNumber}', [CheckoutController::class, 'thanks'])->name('checkout.thanks');
Route::get('/checkout/cancel/{orderNumber}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::get('/checkout/pay/{orderNumber}', [CheckoutController::class, 'pay'])->name('checkout.pay');

// Orders API (for Success page polling)
Route::get('/orders/{orderNumber}', [OrdersController::class, 'show'])->name('orders.show');
Route::get('/orders/{orderNumber}/view', [OrdersController::class, 'view'])->name('orders.view');

// Stripe webhook (no CSRF)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.stripe');
