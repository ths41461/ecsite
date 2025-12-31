<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\ProductImage;
use App\Observers\ProductObserver;
use App\Observers\ProductImageObserver;
use App\Services\ImageService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind ImageService to the container
        $this->app->singleton(ImageService::class, function ($app) {
            return new ImageService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\RecomputeProductMetrics::class,
                \App\Console\Commands\ComputeRankings::class,
            ]);
        }
    }
}
