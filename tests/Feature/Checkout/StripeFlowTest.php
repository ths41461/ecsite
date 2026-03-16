<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Http\Controllers\StripeWebhookController;
use App\Mail\OrderConfirmedMail;

uses(RefreshDatabase::class);

function seedLookups4_4_7(): void {
    (new \Database\Seeders\LookupSeeder())->run();
}

function makeCatalog4_4_7(): array {
    $brandId = DB::table('brands')->insertGetId(['name' => 'TestBrand', 'slug' => 'testbrand', 'created_at' => now(), 'updated_at' => now()]);
    $catId = DB::table('categories')->insertGetId(['name' => 'Cat', 'slug' => 'cat', 'created_at' => now(), 'updated_at' => now()]);
    $productId = DB::table('products')->insertGetId([
        'name' => 'Test Product', 'slug' => 'test-product', 'brand_id' => $brandId, 'category_id' => $catId,
        'is_active' => 1, 'featured' => 0, 'created_at' => now(), 'updated_at' => now()
    ]);
    $variantId = DB::table('product_variants')->insertGetId([
        'product_id' => $productId, 'sku' => 'SKU-TEST', 'price_yen' => 1000, 'sale_price_yen' => null,
        'is_active' => 1, 'created_at' => now(), 'updated_at' => now()
    ]);
    DB::table('inventories')->insert([
        'product_variant_id' => $variantId, 'stock' => 50, 'safety_stock' => 0, 'managed' => 1, 'updated_at' => now()
    ]);
    return [$productId, $variantId];
}

function makePendingOrderForSession(string $sessionId): Order {
    seedLookups4_4_7();
    [$productId, $variantId] = makeCatalog4_4_7();
    $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
    $order = Order::create([
        'order_number' => 'TST' . uniqid(),
        'email' => 'buyer@example.com',
        'name' => 'Buyer',
        'address_line1' => 'N/A',
        'subtotal_yen' => 1000,
        'discount_yen' => 0,
        'shipping_yen' => 0,
        'tax_yen' => 0,
        'total_yen' => 1000,
        'payment_mode' => 'stripe',
        'status' => 'ordered',
        'ordered_at' => now(),
        'order_status_id' => $pendingId,
        'cart_session_id' => $sessionId,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $productId,
        'product_variant_id' => $variantId,
        'name_snapshot' => 'Test Product',
        'sku_snapshot' => 'SKU-TEST',
        'unit_price_yen' => 1000,
        'qty' => 2,
        'line_total_yen' => 2000,
    ]);
    Payment::create([
        'order_id' => $order->id,
        'provider' => 'stripe',
        'type' => 'auth',
        'amount_yen' => 1000,
        'status' => 'created',
        'payload_json' => null,
    ]);
    return $order->fresh(['items','payments']);
}

test('payment_intent_succeeded is idempotent: single tx, inventory decremented once, email once', function () {
    $order = makePendingOrderForSession('sess-abc');
    Mail::fake();

    $pi = (object) [
        'id' => 'pi_test_' . uniqid(),
        'currency' => 'jpy',
        'metadata' => (object) [
            'order_number' => $order->order_number,
            'cart_session_id' => $order->cart_session_id,
        ],
    ];

    $controller = new StripeWebhookController(app(CartService::class), app(InventoryService::class));
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('onPaymentIntentSucceeded');
    $method->setAccessible(true);

    // Call twice to simulate Stripe retry
    $method->invoke($controller, $pi);
    $method->invoke($controller, $pi);

    // One captured transaction with this PI id
    $txCount = DB::table('payment_transactions')->where([
        'provider' => 'stripe',
        'ext_id' => $pi->id,
        'status' => 'captured',
    ])->count();
    expect($txCount)->toBe(1);

    // processed_at set, inventory decremented, confirmation email queued once
    $payment = Payment::where('order_id', $order->id)->latest()->first();
    expect($payment->processed_at)->not->toBeNull();
    $ord = Order::find($order->id);
    expect($ord->inventory_decremented_at)->not->toBeNull();
    expect($ord->confirmation_emailed_at)->not->toBeNull();
    Mail::assertQueued(OrderConfirmedMail::class, 1);
});

test('checkout.session.expired cancels order with reason expired', function () {
    $order = makePendingOrderForSession('sess-exp');
    $session = (object) [
        'metadata' => (object) [
            'order_number' => $order->order_number,
            'cart_session_id' => $order->cart_session_id,
        ],
    ];

    $controller = new StripeWebhookController(app(CartService::class), app(InventoryService::class));
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('onCheckoutExpired');
    $method->setAccessible(true);
    $method->invoke($controller, $session);

    $ord = Order::find($order->id);
    expect($ord->status)->toBe('canceled');
    expect($ord->cancel_reason)->toBe('expired');
});

test('payment_intent.canceled cancels order with reason psp_canceled', function () {
    $order = makePendingOrderForSession('sess-cancel');
    $pi = (object) [
        'metadata' => (object) [
            'order_number' => $order->order_number,
            'cart_session_id' => $order->cart_session_id,
        ],
    ];

    $controller = new StripeWebhookController(app(CartService::class), app(InventoryService::class));
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('onPaymentIntentCanceled');
    $method->setAccessible(true);
    $method->invoke($controller, $pi);

    $ord = Order::find($order->id);
    expect($ord->status)->toBe('canceled');
    expect($ord->cancel_reason)->toBe('psp_canceled');
});

