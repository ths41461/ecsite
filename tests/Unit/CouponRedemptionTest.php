<?php

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

it('allows a coupon to be redeemed once per order and blocks a second redemption', function () {
    // Seed a demo coupon
    $this->seed(\Database\Seeders\CouponDemoSeeder::class);
    $coupon = DB::table('coupons')->where('code', 'WELCOME10')->first();

    $user  = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    // Happy path
    (new \App\Models\Coupon((array)$coupon))->redeem($user->id, $order->id);

    // Duplicate for the same order should fail at DB unique constraint
    expect(function () use ($coupon, $user, $order) {
        (new \App\Models\Coupon((array)$coupon))->redeem($user->id, $order->id);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

it('lets the same coupon be used on a different order', function () {
    $this->seed(\Database\Seeders\CouponDemoSeeder::class);
    $coupon = DB::table('coupons')->where('code', 'WELCOME10')->first();

    $user = User::factory()->create();
    $o1   = Order::factory()->create(['user_id' => $user->id]);
    $o2   = Order::factory()->create(['user_id' => $user->id]);

    (new \App\Models\Coupon((array)$coupon))->redeem($user->id, $o1->id);
    (new \App\Models\Coupon((array)$coupon))->redeem($user->id, $o2->id);

    $count = DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->count();
    expect($count)->toBe(2);
});
