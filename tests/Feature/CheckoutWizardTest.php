<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
    Config::set('session.driver', 'file');
});

it('creates a pending order from the wizard entry point', function () {
    $variantId = DB::table('product_variants')->value('id');

    $session = $this->app['session'];
    $session->start();
    $sessionId = $session->getId();
    $token = $session->token();

    $this
        ->withSession($session->all())
        ->withCookie(config('session.cookie', 'laravel_session'), $sessionId)
        ->withHeader('X-CSRF-TOKEN', $token)
        ->postJson('/cart', ['variant_id' => $variantId, 'qty' => 1])
        ->assertOk();

    $sessionId = app('session')->getId();

    $payload = $this
        ->withCookie(config('session.cookie', 'laravel_session'), $sessionId)
        ->withHeader('X-CSRF-TOKEN', $token)
        ->postJson(route('checkout.order'))
        ->assertOk()
        ->json();

    expect($payload['order']['order_number'] ?? null)->not->toBeNull();
    expect($payload['redirect'])->toBe(route('checkout.details', ['orderNumber' => $payload['order']['order_number']]));

    $orderExists = DB::table('orders')->where('order_number', $payload['order']['order_number'])->exists();
    expect($orderExists)->toBeTrue();
});

it('updates customer details and returns redirect to payment step', function () {
    $variantId = DB::table('product_variants')->value('id');

    $session = $this->app['session'];
    $session->start();
    $sessionId = $session->getId();
    $token = $session->token();

    $this
        ->withSession($session->all())
        ->withCookie(config('session.cookie', 'laravel_session'), $sessionId)
        ->withHeader('X-CSRF-TOKEN', $token)
        ->postJson('/cart', ['variant_id' => $variantId, 'qty' => 1])
        ->assertOk();

    $sessionId = app('session')->getId();

    $orderRes = $this
        ->withCookie(config('session.cookie', 'laravel_session'), $sessionId)
        ->withHeader('X-CSRF-TOKEN', $token)
        ->postJson(route('checkout.order'))
        ->assertOk()
        ->json();
    $orderNumber = $orderRes['order']['order_number'];

    $details = [
        'email' => 'wizard@example.com',
        'name' => 'Wizard Checkout',
        'phone' => '123456789',
        'address_line1' => '1 Test Street',
        'address_line2' => 'Suite 2',
        'city' => 'Test City',
        'state' => 'Tokyo',
        'zip' => '100-0000',
    ];

    $update = $this
        ->withCookie(config('session.cookie', 'laravel_session'), $sessionId)
        ->withHeader('X-CSRF-TOKEN', $token)
        ->postJson(route('checkout.details.update', $orderNumber), $details)
        ->assertOk()
        ->json();

    expect($update['redirect'])->toBe(route('checkout.pay', ['orderNumber' => $orderNumber]));

    $order = DB::table('orders')->where('order_number', $orderNumber)->first();
    expect($order->email)->toBe('wizard@example.com');
    expect($order->name)->toBe('Wizard Checkout');
    expect($order->details_completed_at)->not->toBeNull();
});
