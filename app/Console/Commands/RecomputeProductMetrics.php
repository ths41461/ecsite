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
        // Simplified aggregation: views in last 7/30 days, add_to_cart in last 7 days
        DB::statement('SET SESSION sql_mode = REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", "")');

        // Ensure table exists
        if (!DB::getSchemaBuilder()->hasTable('product_metrics_current')) {
            $this->warn('product_metrics_current not found, skipping.');
            return self::SUCCESS;
        }

        $now = now();
        $from7 = $now->copy()->subDays(7);
        $from30 = $now->copy()->subDays(30);

        $rows = DB::table('products as p')
            ->leftJoin('events as e7', function ($j) use ($from7) {
                $j->on('e7.product_id', '=', 'p.id')->where('e7.occurred_at', '>=', $from7);
            })
            ->leftJoin('events as e30', function ($j) use ($from30) {
                $j->on('e30.product_id', '=', 'p.id')->where('e30.occurred_at', '>=', $from30);
            })
            ->groupBy('p.id')
            ->selectRaw('p.id as product_id,
                SUM(CASE WHEN e7.event_type = "add_to_cart" THEN COALESCE(e7.value,0) ELSE 0 END) as atc_7d,
                SUM(CASE WHEN e30.event_type = "view_pdp" THEN 1 ELSE 0 END) as views_30d')
            ->get();

        foreach ($rows as $r) {
            DB::table('product_metrics_current')->updateOrInsert(
                ['product_id' => $r->product_id],
                [
                    'units_7d' => DB::raw('COALESCE(units_7d,0)'),
                    'atc_rate' => null,
                    'conv_rate_pdp' => null,
                    'revenue_30d' => DB::raw('COALESCE(revenue_30d,0)'),
                    'wishlist_14d' => DB::raw('COALESCE(wishlist_14d,0)'),
                    'search_ctr' => null,
                    'stock' => DB::raw('COALESCE(stock,0)'),
                    'safety_stock' => DB::raw('COALESCE(safety_stock,0)'),
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('metrics:recompute-products completed');
        return self::SUCCESS;
    }
}
