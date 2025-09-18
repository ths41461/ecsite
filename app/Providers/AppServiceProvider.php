<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Observers\ProductObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\RecomputeProductMetrics::class,
                \App\Console\Commands\ReplayStripeEvent::class,
            ]);
        }
    }
}
