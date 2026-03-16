<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeRankings extends Command
{
    protected $signature = 'rank:refresh';
    protected $description = 'Compute and save product rankings to ranking_snapshots table';

    public function handle(): int
    {
        // Get all products with their computed metrics
        $productsWithMetrics = DB::table('product_metrics_current as pmc')
            ->join('products as p', 'pmc.product_id', '=', 'p.id')
            ->select(
                'pmc.product_id',
                'pmc.units_7d',
                'pmc.units_30d',
                'pmc.conv_rate_pdp',
                'pmc.atc_rate',
                'pmc.search_ctr',
                'pmc.revenue_30d',
                'pmc.wishlist_14d',
                'pmc.rating_bayes',
                'pmc.freshness_bonus',
                'pmc.stock',
                'pmc.safety_stock'
            )
            ->where('pmc.stock', '>', 0) // Exclude out-of-stock items
            ->get();

        $computedAt = now(); // Use a single timestamp for all rankings in this execution
        
        // Clear existing overall rankings before inserting new ones
        DB::table('ranking_snapshots')->where('scope', 'overall')->delete();
        
        // Calculate scores for each product and store in an array
        $productScores = [];
        foreach ($productsWithMetrics as $product) {
            // Normalize values (this is a simplified approach)
            // In a real system, you'd want more sophisticated normalization
            $normalizedUnits7d = $this->normalize($product->units_7d, 0, 100); // Assuming max 100 units
            $normalizedUnits30d = $this->normalize($product->units_30d, 0, 500); // Assuming max 500 units
            $convRate = $product->conv_rate_pdp ?? 0;
            $atcRate = $product->atc_rate ?? 0;
            $rating = $product->rating_bayes ?? 0;
            $wishlist = $this->normalize($product->wishlist_14d, 0, 50); // Assuming max 50 wishlist adds
            $revenue = $this->normalize($product->revenue_30d, 0, 50000); // Assuming max 50k revenue
            $searchCtr = $product->search_ctr ?? 0;
            $freshness = $product->freshness_bonus ?? 0;

            // Apply weights from the formula:
            // score = 0.28*units_7d + 0.18*units_30d + 0.14*conv_rate_pdp +
            //         0.10*atc_rate + 0.10*bayes_star_rating +
            //         0.06*wishlist_14d + 0.06*revenue_30d +
            //         0.04*search_ctr + 0.04*freshness_bonus

            $score = 
                (0.28 * $normalizedUnits7d) +
                (0.18 * $normalizedUnits30d) +
                (0.14 * $convRate) +
                (0.10 * $atcRate) +
                (0.10 * $rating) +
                (0.06 * $wishlist) +
                (0.06 * $revenue) +
                (0.04 * $searchCtr) +
                (0.04 * $freshness);

            $productScores[] = [
                'product_id' => $product->product_id,
                'score' => $score,
            ];
        }

        // Sort products by score in descending order
        usort($productScores, function($a, $b) {
            return $b['score'] <=> $a['score']; // Sort descending by score
        });

        // Insert the top 10 products with their ranks
        foreach (array_slice($productScores, 0, 10) as $index => $productScore) {
            $rank = $index + 1;
            DB::table('ranking_snapshots')->insert([
                'scope' => 'overall',
                'rank' => $rank,
                'product_id' => $productScore['product_id'],
                'score' => $productScore['score'],
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ]);
        }

        $this->info('rank:refresh completed');
        return self::SUCCESS;
    }

    private function normalize($value, $min, $max): float
    {
        if ($max <= $min) return 0;
        return max(0, min(1, ($value - $min) / ($max - $min)));
    }

    private function computeRanks($scope): void
    {
        // Get products ordered by score (highest first), limited to top 10
        $products = DB::table('ranking_snapshots')
            ->where('scope', $scope)
            ->where('computed_at', DB::table('ranking_snapshots')->where('scope', $scope)->max('computed_at'))
            ->orderByDesc('score')
            ->limit(10)
            ->get(['id', 'score']);

        // Update ranks for the top products
        foreach ($products as $index => $product) {
            $rank = $index + 1;
            DB::table('ranking_snapshots')
                ->where('id', $product->id)
                ->update(['rank' => $rank]);
        }

        // Remove any rankings beyond the top 10
        DB::table('ranking_snapshots')
            ->where('scope', $scope)
            ->where('computed_at', DB::table('ranking_snapshots')->where('scope', $scope)->max('computed_at'))
            ->where('rank', '>', 10)
            ->delete();
    }
}