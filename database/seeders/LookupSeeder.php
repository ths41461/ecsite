<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // --- order_statuses (from 3.5) ---
        if (Schema()->hasTable('order_statuses')) {
            $orderRows = [
                ['code' => 'pending',   'name' => 'Pending',   'created_at' => $now, 'updated_at' => $now],
                ['code' => 'paid',      'name' => 'Paid',      'created_at' => $now, 'updated_at' => $now],
                ['code' => 'fulfilled', 'name' => 'Fulfilled', 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'cancelled', 'name' => 'Cancelled', 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'refunded',  'name' => 'Refunded',  'created_at' => $now, 'updated_at' => $now],
            ];
            DB::table('order_statuses')->upsert($orderRows, ['code'], ['name', 'updated_at']);

            $pendingOrderId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
            if ($pendingOrderId) {
                DB::table('orders')->whereNull('order_status_id')->update(['order_status_id' => $pendingOrderId]);
            }
        }

        // --- payment_statuses (new in 3.6) ---
        if (Schema()->hasTable('payment_statuses')) {
            $payRows = [
                ['code' => 'pending',    'name' => 'Pending',    'created_at' => $now, 'updated_at' => $now],
                ['code' => 'authorized', 'name' => 'Authorized', 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'captured',   'name' => 'Captured',   'created_at' => $now, 'updated_at' => $now],
                ['code' => 'failed',     'name' => 'Failed',     'created_at' => $now, 'updated_at' => $now],
                ['code' => 'refunded',   'name' => 'Refunded',   'created_at' => $now, 'updated_at' => $now],
            ];
            DB::table('payment_statuses')->upsert($payRows, ['code'], ['name', 'updated_at']);

            $pendingPayId = (int) DB::table('payment_statuses')->where('code', 'pending')->value('id');
            if ($pendingPayId && Schema()->hasTable('payments')) {
                DB::table('payments')->whereNull('payment_status_id')->update(['payment_status_id' => $pendingPayId]);
            }
        }
    }
}

/** tiny helper so this file doesn't need Illuminate\Support\Facades\Schema import */
if (!function_exists('Schema')) {
    function Schema()
    {
        return app('db.schema');
    }
}
