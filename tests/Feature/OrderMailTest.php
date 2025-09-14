<?php

use App\Http\Controllers\StripeWebhookController;
use App\Mail\OrderCanceledMail;
use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function seedLookups(): void {
    \Database\Seeders\LookupSeeder::class;
    (new \Database\Seeders\LookupSeeder())->run();
}

function makeCatalog(): array {
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
        'product_variant_id' => $variantId, 'stock' => 100, 'safety_stock' => 0, 'managed' => 1, 'updated_at' => now()
    ]);
    return [$productId, $variantId];
}

test('it_sends_canceled_email_on_cancel_route', function () {
    seedLookups();
    [$productId, $variantId] = makeCatalog();

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
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $productId,
        'product_variant_id' => $variantId,
        'name_snapshot' => 'Test Product',
        'sku_snapshot' => 'SKU-TEST',
        'unit_price_yen' => 1000,
        'qty' => 1,
        'line_total_yen' => 1000,
    ]);
    Payment::create([
        'order_id' => $order->id,
        'provider' => 'stripe',
        'type' => 'auth',
        'amount_yen' => 1000,
        'status' => 'created',
        'payload_json' => null,
    ]);

    Mail::fake();

    // Simulate session
    $this->withSession([])->get('/checkout/cancel/' . $order->order_number)->assertStatus(200);

    Mail::assertQueued(OrderCanceledMail::class, function ($m) use ($order) {
        return $m->order->id === $order->id;
    });
});

test('it_sends_confirmed_email_on_webhook_success', function () {
    seedLookups();
    [$productId, $variantId] = makeCatalog();

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
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $productId,
        'product_variant_id' => $variantId,
        'name_snapshot' => 'Test Product',
        'sku_snapshot' => 'SKU-TEST',
        'unit_price_yen' => 1000,
        'qty' => 1,
        'line_total_yen' => 1000,
    ]);
    Payment::create([
        'order_id' => $order->id,
        'provider' => 'stripe',
        'type' => 'auth',
        'amount_yen' => 1000,
        'status' => 'created',
        'payload_json' => null,
    ]);

    // Build a fake PI object
    $pi = (object) [
        'id' => 'pi_test_' . uniqid(),
        'currency' => 'jpy',
        'metadata' => (object) ['order_number' => $order->order_number],
    ];

    Mail::fake();

    // Call the private handler via reflection
    $controller = new StripeWebhookController(app(CartService::class), app(InventoryService::class));
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('onPaymentIntentSucceeded');
    $method->setAccessible(true);
    $method->invoke($controller, $pi);

    Mail::assertQueued(OrderConfirmedMail::class, function ($m) use ($order) {
        return $m->order->id === $order->id;
    });
    expect(Order::find($order->id)->confirmation_emailed_at)->not->toBeNull();
});

