<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
use App\Services\ImageService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageService::class, function ($app) {
            return new ImageService;
        });
    }

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
