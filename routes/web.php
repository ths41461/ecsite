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
    // Get recommended products from the ranking snapshots (top 8)
    $recommendedProducts = \App\Models\RankingSnapshot::where('scope', 'overall')
        ->where('computed_at', \App\Models\RankingSnapshot::where('scope', 'overall')->max('computed_at'))
        ->join('products', 'ranking_snapshots.product_id', '=', 'products.id')
        ->leftJoinSub(
            \App\Models\Review::select('product_id', \DB::raw('AVG(rating) as avg_rating'))
                ->where('approved', true)
                ->groupBy('product_id'),
            'review_avg',
            'products.id',
            '=',
            'review_avg.product_id'
        )
        ->leftJoinSub(
            \App\Models\Review::select('product_id', \DB::raw('COUNT(*) as review_count'))
                ->where('approved', true)
                ->groupBy('product_id'),
            'review_count',
            'products.id',
            '=',
            'review_count.product_id'
        )
        ->select([
            'ranking_snapshots.*',
            'products.*',
            'review_avg.avg_rating as average_rating',
            'review_count.review_count as review_count'
        ])
        ->with([
            'product.brand:id,name,slug',
            'product.heroImage:id,product_id,path,alt,rank',
            'product.variants:id,product_id,price_yen,sale_price_yen,option_json,is_active',
        ])
        ->orderBy('ranking_snapshots.rank')
        ->limit(8)
        ->get()
        ->map(function ($snapshot) {
            /** @var \App\Models\RankingSnapshot $snapshot */
            $product = $snapshot->product;
            $imagePath = $product->heroImage?->path;
            $imageUrl  = $imagePath
                ? (str_starts_with($imagePath, 'http') || str_starts_with($imagePath, '/') ? $imagePath : \Illuminate\Support\Facades\Storage::url($imagePath))
                : null;

            // Calculate the price from variants
            $minPriceYen = $product->variants->min('price_yen') ?? 0;
            $minSaleYen = $product->variants->whereNotNull('sale_price_yen')->min('sale_price_yen');

            $finalPrice = $minSaleYen ?? $minPriceYen;

            // Extract gender and size information from variants
            $genders = collect();
            $sizes = collect();
            
            foreach ($product->variants as $variant) {
                if ($variant->option_json) {
                    if (isset($variant->option_json['gender'])) {
                        $genders->push($variant->option_json['gender']);
                    }
                    if (isset($variant->option_json['size_ml'])) {
                        $sizes->push($variant->option_json['size_ml']);
                    }
                }
            }
            
            $uniqueGenders = $genders->unique()->values();
            $uniqueSizes = $sizes->unique()->values();

            return [
                'id' => $product->id,
                'productImageSrc' => $imageUrl,
                'category' => $product->brand?->name ?? 'ブランド名',
                'productName' => $product->name,
                'price' => '¥' . number_format($finalPrice),
                'slug' => $product->slug,
                'rank' => $snapshot->rank,
                'score' => $snapshot->score,
                'genders' => $uniqueGenders->toArray(),
                'sizes' => $uniqueSizes->toArray(),
                'averageRating' => round($snapshot->average_rating ?? 0, 1),
                'reviewCount' => $snapshot->review_count ?? 0,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price_cents' => $variant->sale_price_yen !== null ? (int) $variant->sale_price_yen * 100 : (int) $variant->price_yen * 100,
                        'compare_at_cents' => $variant->sale_price_yen !== null ? (int) $variant->price_yen * 100 : null,
                        'stock' => $variant->inventory?->stock,
                        'safety_stock' => $variant->inventory?->safety_stock,
                        'managed' => (bool) ($variant->inventory?->managed ?? false),
                        'options' => $variant->option_json,
                    ];
                })->toArray(),
            ];
        })
        ->toArray();

    return Inertia::render('homepage', [
        'recommendedProducts' => $recommendedProducts,
    ]);
})->name('home');

Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/products/{product}', [ProductController::class, 'show'])
    ->name('products.show');

// Additional pages
Route::get('/fragrance-diagnosis', function () {
    return Inertia::render('FragranceDiagnosis');
})->name('fragrance.diagnosis');

Route::get('/brand-introduction', function () {
    return Inertia::render('BrandIntroduction');
})->name('brand.introduction');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

// Search autocomplete API
Route::get('/api/search/autocomplete', [ProductController::class, 'autocomplete'])
    ->name('search.autocomplete');

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
    // Coupons (register specific routes before parameterized /cart/{line})
    Route::post('/cart/coupon/preview', [CartController::class, 'previewCoupon'])->name('cart.coupon.preview');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{line}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{line}', [CartController::class, 'destroy'])->name('cart.destroy');
});



use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Checkout routes that require authentication
    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('checkout/order', [CheckoutController::class, 'createOrder'])->name('checkout.order');
    Route::get('checkout/{orderNumber}/details', [CheckoutController::class, 'details'])->name('checkout.details');
    Route::post('checkout/{orderNumber}/details', [CheckoutController::class, 'updateDetails'])->name('checkout.details.update');
    // Spec parity aliases
    Route::post('checkout/create', [CheckoutController::class, 'store'])->name('checkout.create');
    Route::get('checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('checkout/thanks/{orderNumber}', [CheckoutController::class, 'thanks'])->name('checkout.thanks');
    Route::get('checkout/cancel/{orderNumber}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('checkout/pay/{orderNumber}', [CheckoutController::class, 'pay'])->name('checkout.pay');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

// Orders API (for Success page polling)
Route::get('/orders/{orderNumber}', [OrdersController::class, 'show'])->name('orders.show');
Route::get('/orders/{orderNumber}/view', [OrdersController::class, 'view'])->name('orders.view');

// Reviews
Route::get('/products/{product}/reviews', [App\Http\Controllers\ReviewController::class, 'index'])->name('reviews.index');
Route::post('/products/{product}/reviews', [App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
Route::put('/reviews/{review}', [App\Http\Controllers\ReviewController::class, 'update'])->name('reviews.update');
Route::delete('/reviews/{review}', [App\Http\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');

// Shipment tracking
Route::get('/shipment/track', function () {
    return view('shipment.tracking');
})->name('shipment.track');

// Stripe webhook (no CSRF)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.stripe');