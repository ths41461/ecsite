<?php

use App\Services\CartService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Redis::flushdb();
    // Seed a product with variants/inventory (your DatabaseSeeder already does a lot)
    $this->artisan('db:seed'); // or seed a smaller dedicated seeder for speed
});

it('adds, updates, removes lines and computes totals from server-side prices', function () {
    $svc = app(CartService::class);

    // Find any variant that exists
    $variant = DB::table('product_variants')->inRandomOrder()->first();
    expect($variant)->not->toBeNull();

    $sessionId = 'test-session-123';

    // Add 2 qty
    $cart = $svc->add($sessionId, $variant->id, 2);
    expect($cart['lines'])->toHaveCount(1);
    $line = $cart['lines'][0];
    expect($line['variant_id'])->toBe($variant->id);
    expect($line['qty'])->toBeGreaterThan(0);
    expect($cart['subtotal_cents'])->toBe($line['line_total_cents']);

    // Update to 5
    $cart2 = $svc->update($sessionId, (string)$variant->id, 5);
    $line2 = $cart2['lines'][0];
    expect($line2['qty'])->toBe(5);

    // Remove
    $cart3 = $svc->remove($sessionId, (string)$variant->id);
    expect($cart3['lines'])->toHaveCount(0);
    expect($cart3['subtotal_cents'])->toBe(0);
});
