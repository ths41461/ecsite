<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

it('returns an empty cart on GET /cart initially', function () {
    $res = $this->getJson('/cart')
        ->assertOk()
        ->json();

    expect($res['lines'])->toBeArray()->toBeEmpty();
    expect($res['subtotal_cents'])->toBe(0);
    expect($res['total_cents'])->toBe(0);
    expect($res['currency'])->toBe('JPY');
});

it('adds a line on POST /cart and returns computed cart', function () {
    $vid = DB::table('product_variants')->value('id');
    expect($vid)->not->toBeNull();

    $res = $this->postJson('/cart', [
        'variant_id' => $vid,
        'qty' => 2,
    ])->assertOk()->json();

    expect($res['lines'])->toHaveCount(1);
    expect($res['lines'][0]['variant_id'])->toBe($vid);
    expect($res['lines'][0]['qty'])->toBeGreaterThanOrEqual(1);
    expect($res['subtotal_cents'])->toBeGreaterThan(0);
});

it('rejects invalid variants on POST /cart', function () {
    $invalidVariant = (int) DB::table('product_variants')->max('id') + 999;

    $this->postJson('/cart', [
        'variant_id' => $invalidVariant,
        'qty' => 1,
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['variant_id']);
});

it('updates a line on PATCH /cart/{line}', function () {
    $vid = DB::table('product_variants')->value('id');
    $this->postJson('/cart', ['variant_id' => $vid, 'qty' => 1])->assertOk();

    $res = $this->patchJson("/cart/{$vid}", ['qty' => 5])->assertOk()->json();

    expect($res['lines'][0]['qty'])->toBe(5);
});

it('removes a line on DELETE /cart/{line}', function () {
    $vid = DB::table('product_variants')->value('id');
    $this->postJson('/cart', ['variant_id' => $vid, 'qty' => 1])->assertOk();

    $res = $this->deleteJson("/cart/{$vid}")->assertOk()->json();

    expect($res['lines'])->toBeArray()->toBeEmpty();
    expect($res['total_cents'])->toBe(0);
});
