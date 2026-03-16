<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecomputeProductMetrics extends Command
{
    protected $signature = 'metrics:recompute-products';
    protected $description = 'Recompute product_metrics_current from recent events and orders';

    public function handle(): int
    {
        // Set SQL mode to handle aggregations properly
        DB::statement('SET SESSION sql_mode = REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", "")');

        // Ensure table exists
        if (!DB::getSchemaBuilder()->hasTable('product_metrics_current')) {
            $this->warn('product_metrics_current not found, skipping.');
            return self::SUCCESS;
        }

        $now = now();
        $from7d = $now->copy()->subDays(7);
        $from14d = $now->copy()->subDays(14);
        $from30d = $now->copy()->subDays(30);

        // Get all product IDs to ensure all products are included
        $allProductIds = DB::table('products')->pluck('id');

        foreach ($allProductIds as $productId) {
            // Compute units sold in last 7 and 30 days (from orders)
            $units7d = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->where('orders.status', 'delivered') // Assuming delivered orders count as sold
                ->where('orders.created_at', '>=', $from7d)
                ->sum('order_items.qty');
                
            $units30d = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->where('orders.status', 'delivered')
                ->where('orders.created_at', '>=', $from30d)
                ->sum('order_items.qty');

            // Compute views, add-to-cart, and wishlist adds
            $eventsData = DB::table('events')
                ->selectRaw('
                    SUM(CASE WHEN event_type = "view_pdp" AND occurred_at >= ? THEN 1 ELSE 0 END) as views_7d,
                    SUM(CASE WHEN event_type = "view_pdp" AND occurred_at >= ? THEN 1 ELSE 0 END) as views_30d,
                    SUM(CASE WHEN event_type = "add_to_cart" AND occurred_at >= ? THEN COALESCE(value, 0) ELSE 0 END) as atc_7d,
                    SUM(CASE WHEN event_type = "wishlist_add" AND occurred_at >= ? THEN 1 ELSE 0 END) as wishlist_14d
                ', [$from7d, $from30d, $from7d, $from14d])
                ->where('product_id', $productId)
                ->first();

            // Compute conversion rate (add-to-cart / views) with safety check
            $convRate = $eventsData->views_7d > 0 ? 
                round($eventsData->atc_7d / $eventsData->views_7d, 4) : 0;
                
            // Compute ATC rate (add-to-cart / views) as well
            $atcRate = $eventsData->views_7d > 0 ? 
                round($eventsData->atc_7d / $eventsData->views_7d, 4) : 0;

            // Compute revenue from completed orders
            $revenue30d = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->where('orders.status', 'delivered')
                ->where('orders.created_at', '>=', $from30d)
                ->sum('order_items.line_total_yen');

            // Get search metrics (impressions and clicks)
            $searchData = DB::table('events')
                ->selectRaw('
                    SUM(CASE WHEN event_type = "search_impr" THEN 1 ELSE 0 END) as search_impressions,
                    SUM(CASE WHEN event_type = "search_click" THEN 1 ELSE 0 END) as search_clicks
                ')
                ->where('product_id', $productId)
                ->where('occurred_at', '>=', $from30d)
                ->first();
                
            $searchCtr = $searchData->search_impressions > 0 ? 
                round($searchData->search_clicks / $searchData->search_impressions, 4) : 0;

            // Get Bayesian average rating
            $ratingData = DB::table('reviews')
                ->selectRaw('AVG(rating) as avg_rating, COUNT(rating) as count')
                ->where('product_id', $productId)
                ->where('approved', true)
                ->first();
                
            // Bayesian average calculation (with prior of 3 stars and 5 sample size)
            $bayesRating = 0;
            if ($ratingData->count > 0) {
                $priorMean = 3.0; // Prior mean rating
                $priorSampleSize = 5; // Prior sample size
                $bayesRating = round(
                    (($priorMean * $priorSampleSize) + ($ratingData->avg_rating * $ratingData->count)) / 
                    ($priorSampleSize + $ratingData->count), 4
                );
            }

            // Get stock information
            $stockInfo = DB::table('product_variants')
                ->join('inventories', 'product_variants.id', '=', 'inventories.product_variant_id')
                ->where('product_variants.product_id', $productId)
                ->selectRaw('SUM(inventories.stock) as total_stock, SUM(inventories.safety_stock) as total_safety_stock')
                ->first();

            // Calculate freshness bonus (based on how recently the product was added)
            $product = DB::table('products')
                ->select('created_at')
                ->where('id', $productId)
                ->first();
                
            $daysSinceCreation = $product ? now()->diffInDays($product->created_at) : 0;
            // Freshness bonus decreases over time (newer products get higher bonus)
            $freshnessBonus = max(0, round(1.0 / max(1, $daysSinceCreation * 0.1), 4));

            // Update or insert the metrics for this product
            DB::table('product_metrics_current')->updateOrInsert(
                ['product_id' => $productId],
                [
                    'units_7d' => $units7d,
                    'units_30d' => $units30d,
                    'conv_rate_pdp' => $convRate,
                    'atc_rate' => $atcRate,
                    'search_ctr' => $searchCtr,
                    'revenue_30d' => $revenue30d,
                    'wishlist_14d' => $eventsData->wishlist_14d ?? 0,
                    'rating_bayes' => $bayesRating,
                    'freshness_bonus' => $freshnessBonus,
                    'stock' => $stockInfo->total_stock ?? 0,
                    'safety_stock' => $stockInfo->total_safety_stock ?? 0,
                ]
            );
        }

        $this->info('metrics:recompute-products completed');
        return self::SUCCESS;
    }
}