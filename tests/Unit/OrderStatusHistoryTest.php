<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Make sure tables/migrations exist and seed lookups
    $this->artisan('migrate');
    $this->seed(\Database\Seeders\LookupSeeder::class);
});

it('transitions order from pending to paid and writes a history row', function () {
    // Arrange: create order in pending
    $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
    $paidId    = (int) DB::table('order_statuses')->where('code', 'paid')->value('id');

    $order = Order::factory()->create(['order_status_id' => $pendingId]);

    // Act
    $order->transitionTo('paid');

    // Assert order status updated
    $order->refresh();
    expect($order->order_status_id)->toBe($paidId);

    // Assert exactly one history row
    $history = DB::table('order_status_history')->where('order_id', $order->id)->get();
    expect($history)->toHaveCount(1);
    expect($history[0]->from_status_id)->toBe($pendingId);
    expect($history[0]->to_status_id)->toBe($paidId);
});

it('rejects unknown status codes', function () {
    $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
    $order = Order::factory()->create(['order_status_id' => $pendingId]);

    expect(fn() => $order->transitionTo('does-not-exist'))
        ->toThrow(InvalidArgumentException::class);
});
