<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CouponDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('coupons')->updateOrInsert(
            ['code' => 'WELCOME10'],
            [
                'description' => '10% off for first order',
                'type'        => 'percent',
                'value'       => 10,
                'starts_at'   => now()->subDay(),
                'ends_at'     => now()->addMonth(),
                'max_uses'    => 1000,
                'used_count'  => 0,
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }
}
