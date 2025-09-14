<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Decrement managed inventory for each item in the order.
     * Idempotent at call-site: caller must guard with orders.inventory_decremented_at.
     */
    public function decrementForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $variantId = (int) $item->product_variant_id;
                $qty = (int) $item->qty;
                if ($variantId <= 0 || $qty <= 0) continue;

                // Update only managed inventories; clamp to >= 0
                $affected = DB::table('inventories')
                    ->where('product_variant_id', $variantId)
                    ->where('managed', true)
                    ->update([
                        'stock' => DB::raw('GREATEST(stock - ' . $qty . ', 0)'),
                        'updated_at' => now(),
                    ]);

                if ($affected === 0) {
                    Log::warning('Inventory decrement skipped (unmanaged or missing)', [
                        'order_id' => $order->id,
                        'variant_id' => $variantId,
                        'qty' => $qty,
                    ]);
                }
            }

            // Mark order decremented
            $order->forceFill(['inventory_decremented_at' => now()])->save();
        });
    }
}

