<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

it('returns 422 for bad payloads', function () {
    // missing variant_id
    $this->postJson('/cart', ['qty' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['variant_id']);

    // invalid qty
    $vid = DB::table('product_variants')->value('id');
    $this->postJson('/cart', ['variant_id' => $vid, 'qty' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qty']);
});

it('throttles excessive mutations', function () {
    $vid = DB::table('product_variants')->value('id');

    // First add to create a line
    $this->postJson('/cart', ['variant_id' => $vid, 'qty' => 1])->assertOk();

    // Hit PATCH more than limiter (20/min)
    for ($i = 0; $i < 25; $i++) {
        $resp = $this->patchJson("/cart/{$vid}", ['qty' => 1]);
        if ($i < 20) {
            $resp->assertOk();
        } else {
            $resp->assertStatus(429); // Too Many Requests
            break;
        }
    }
});
