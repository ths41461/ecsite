<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        // Ensure slug is unique
        $originalSlug = $product->slug;
        $counter = 1;

        while (Product::where('slug', $product->slug)->exists()) {
            $product->slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }

    public function created(Product $product): void
    {
        // Queue Scout indexing (honors config('scout.queue'))
        $product->searchable();
    }

    public function updating(Product $product): void
    {
        // If the name changed and slug is based on name, update the slug
        if ($product->isDirty('name') && $product->getOriginal('name') !== $product->name) {
            $originalSlug = Str::slug($product->name);
            $counter = 1;
            $newSlug = $originalSlug;

            // Check if this slug already exists for other products
            while (Product::where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                $newSlug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product->slug = $newSlug;
        }
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
