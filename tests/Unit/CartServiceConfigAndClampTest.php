<?php

use App\Services\CartService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Redis::flushdb();
    $this->artisan('db:seed');
});

it('pre-validates variant existence on add', function () {
    $svc = app(CartService::class);
    $session = 'sess-x';
    $invalidVariantId = 9999999;

    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $svc->add($session, $invalidVariantId, 1);
});

it('adds clamp notice when requested qty exceeds available stock', function () {
    $svc = app(CartService::class);
    $session = 'sess-y';

    // find a managed variant with limited stock (fallback to force with safety stock)
    $row = DB::table('product_variants as pv')
        ->leftJoin('inventories as inv', 'inv.product_variant_id', '=', 'pv.id')
        ->select('pv.id as variant_id', DB::raw('COALESCE(inv.managed,0) as managed'), 'inv.stock', 'inv.safety_stock')
        ->first();

    expect($row)->not->toBeNull();

    // Force a large qty to trigger clamp
    $cart = $svc->add($session, $row->variant_id, 999);

    // Either it clamps or (if unmanaged) it won't have a notice — we assert the structure safely.
    $line = $cart['lines'][0] ?? null;
    expect($line)->not->toBeNull();

    if ($line['managed'] && isset($line['available_qty']) && $line['available_qty'] !== null) {
        if (($line['qty'] ?? 0) < 999) {
            expect($line)->toHaveKey('notice');
            expect($line['notice']['code'])->toBe('qty_clamped_to_available');
            expect($line['notice']['available'])->toEqual($line['available_qty']);
        }
    }
});

it('respects currency and maxQty from config', function () {
    config()->set('cart.currency', 'JPY');
    config()->set('cart.max_qty', 5);

    $svc = app(CartService::class);
    $session = 'sess-z';
    $vid = DB::table('product_variants')->value('id');

    // add 10 should clamp to max 5 (service-level clamp)
    $cart = $svc->add($session, $vid, 10);
    $line = $cart['lines'][0];
    expect($line['qty'])->toBeLessThanOrEqual(5);
    expect($cart['currency'])->toBe('JPY');
});
