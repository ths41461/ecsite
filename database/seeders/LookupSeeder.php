<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        // order_statuses
        $now = now();
        $rows = [
            ['code' => 'pending',   'name' => 'Pending',   'created_at' => $now, 'updated_at' => $now],
            ['code' => 'paid',      'name' => 'Paid',      'created_at' => $now, 'updated_at' => $now],
            ['code' => 'fulfilled', 'name' => 'Fulfilled', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'cancelled', 'name' => 'Cancelled', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'refunded',  'name' => 'Refunded',  'created_at' => $now, 'updated_at' => $now],
        ];

        // upsert on code keeps it idempotent
        DB::table('order_statuses')->upsert($rows, ['code'], ['name', 'updated_at']);

        // Backfill existing orders: set missing status to 'pending'
        $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');

        DB::table('orders')
            ->whereNull('order_status_id')
            ->update(['order_status_id' => $pendingId]);
    }
}
