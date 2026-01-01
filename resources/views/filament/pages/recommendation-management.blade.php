<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Recommendation System Management
            </x-slot>

            <x-slot name="description">
                Manage the product recommendation system and compute rankings
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 dark:bg-gray-900/20 p-6 rounded-xl border">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ranking System</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        The ranking system calculates product scores based on multiple metrics including sales, 
                        conversion rates, ratings, and more. Use the button below to manually trigger a 
                        recalculation of all product rankings.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Last ranking computation: {{ $this->lastRankingRun ?? 'Never run' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/20 p-6 rounded-xl border">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Product Metrics</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Product metrics are collected from various sources including orders, views, 
                        add-to-cart events, and ratings. Recompute metrics to update the underlying 
                        data used for rankings.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Last metrics computation: {{ $this->lastMetricsRun ?? 'Never run' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t">
                <h3 class="text-md font-medium text-gray-900 dark:text-white mb-4">Ranking Algorithm</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    The ranking algorithm uses a weighted formula combining multiple factors:
                </p>
                <ul class="list-disc pl-5 space-y-2 text-gray-600 dark:text-gray-300">
                    <li><strong>Units Sold (7d)</strong>: 28% weight</li>
                    <li><strong>Units Sold (30d)</strong>: 18% weight</li>
                    <li><strong>Conversion Rate</strong>: 14% weight</li>
                    <li><strong>Add-to-Cart Rate</strong>: 10% weight</li>
                    <li><strong>Bayesian Average Rating</strong>: 10% weight</li>
                    <li><strong>Wishlist Additions (14d)</strong>: 6% weight</li>
                    <li><strong>Revenue (30d)</strong>: 6% weight</li>
                    <li><strong>Search CTR</strong>: 4% weight</li>
                    <li><strong>Freshness Bonus</strong>: 4% weight</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>