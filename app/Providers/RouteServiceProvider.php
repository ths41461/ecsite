<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;



class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // ...
        RateLimiter::for('cart-mutations', function (Request $request) {
            // 20 writes/min per IP + method + route name
            // Keep POST, PATCH, DELETE buckets separate to match tests.
            $routeName = optional($request->route())->getName();
            $key = sprintf('%s|%s|%s', $request->ip(), $request->method(), $routeName);
            return Limit::perMinute(20)->by($key);
        });
    }
}
