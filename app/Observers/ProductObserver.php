<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        // Queue Scout indexing (honors config('scout.queue'))
        $product->searchable();
    }

    public function updated(Product $product): void
    {
        $product->searchable();
    }

    public function deleted(Product $product): void
    {
        // Remove from index on soft/hard delete
        $product->unsearchable();
    }

    public function restored(Product $product): void
    {
        $product->searchable();
    }
}
