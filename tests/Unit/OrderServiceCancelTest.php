<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->artisan('migrate');
    $this->seed(\Database\Seeders\LookupSeeder::class);
});

afterEach(function () {
    Mockery::close();
});

it('does not cancel orders that already have a captured payment transaction', function () {
    $order = Order::factory()->create([
        'cart_session_id' => 'sess-paid',
        'status' => 'ordered',
    ]);

    $pendingPaymentId = (int) DB::table('payment_statuses')->where('code', 'pending')->value('id');

    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'stripe',
        'type' => 'auth',
        'amount_yen' => $order->total_yen,
        'status' => 'created',
        'payment_status_id' => $pendingPaymentId,
        'payload_json' => [],
    ]);

    DB::table('payment_transactions')->insert([
        'payment_id' => $payment->id,
        'provider' => 'stripe',
        'ext_id' => 'pi_123',
        'amount_yen' => $order->total_yen,
        'currency' => 'JPY',
        'status' => 'captured',
        'payload_json' => json_encode([]),
        'occurred_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cart = Mockery::mock(CartService::class);
    $cart->shouldNotReceive('clear');
    $cart->shouldNotReceive('add');

    $service = new OrderService($cart);

    $result = $service->cancelIfNotPaid($order->load('payments'), 'expired');

    $result->refresh();
    expect($result->status)->toBe('ordered')
        ->and($result->canceled_at)->toBeNull()
        ->and($result->cancel_reason)->toBeNull();
});

it('cancels and restores cart items when payment has not been captured', function () {
    $variant = ProductVariant::factory()->create();

    $order = Order::factory()->create([
        'cart_session_id' => 'sess-unpaid',
        'status' => 'ordered',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'name_snapshot' => $variant->product->name,
        'sku_snapshot' => $variant->sku,
        'unit_price_yen' => 1000,
        'qty' => 2,
        'line_total_yen' => 2000,
    ]);

    $pendingPaymentId = (int) DB::table('payment_statuses')->where('code', 'pending')->value('id');

    Payment::create([
        'order_id' => $order->id,
        'provider' => 'stripe',
        'type' => 'auth',
        'amount_yen' => $order->total_yen,
        'status' => 'created',
        'payment_status_id' => $pendingPaymentId,
        'payload_json' => [],
    ]);

    $cart = Mockery::mock(CartService::class);
    $cart->shouldReceive('clear')->once()->with('sess-unpaid');
    $cart->shouldReceive('add')->once()->with('sess-unpaid', $variant->id, 2)->andReturn([]);

    $service = new OrderService($cart);

    $result = $service->cancelIfNotPaid($order->load(['items', 'payments']), 'expired');

    expect($result->status)->toBe('canceled')
        ->and($result->cancel_reason)->toBe('expired')
        ->and($result->canceled_at)->not->toBeNull()
        ->and($result->pending_expires_at)->not->toBeNull();
});
