<?php

namespace App\Services;

use App\Models\Inventory;
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

    /**
     * Update inventory stock for a specific product variant
     */
    public function updateStock(int $productVariantId, int $newStock): bool
    {
        $inventory = Inventory::where('product_variant_id', $productVariantId)->first();
        
        if (!$inventory) {
            Log::error('Inventory record not found for variant', ['variant_id' => $productVariantId]);
            return false;
        }

        $inventory->updateStock($newStock);
        return true;
    }

    /**
     * Increment inventory stock for a specific product variant
     */
    public function incrementStock(int $productVariantId, int $amount): bool
    {
        $inventory = Inventory::where('product_variant_id', $productVariantId)->first();
        
        if (!$inventory) {
            Log::error('Inventory record not found for variant', ['variant_id' => $productVariantId]);
            return false;
        }

        $inventory->incrementStock($amount);
        return true;
    }

    /**
     * Decrement inventory stock for a specific product variant
     */
    public function decrementStock(int $productVariantId, int $amount): bool
    {
        $inventory = Inventory::where('product_variant_id', $productVariantId)->first();
        
        if (!$inventory) {
            Log::error('Inventory record not found for variant', ['variant_id' => $productVariantId]);
            return false;
        }

        $inventory->decrementStock($amount);
        return true;
    }

    /**
     * Get inventory records with low stock status
     */
    public function getLowStockInventories(int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Inventory::whereColumn('stock', '<=', 'safety_stock')
                          ->where('stock', '>', 0)
                          ->with('variant.product');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get inventory records that are out of stock
     */
    public function getOutOfStockInventories(int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Inventory::where('stock', 0)
                          ->with('variant.product');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get inventory records that are in stock
     */
    public function getInStockInventories(int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Inventory::where('stock', '>', 0)
                          ->with('variant.product');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}