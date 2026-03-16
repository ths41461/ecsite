<?php

echo "=== Product Verification Script ===\n\n";

$products = App\Models\Product::with(['brand', 'category', 'variants', 'variants.inventory'])
    ->limit(10)
    ->get();

echo 'Total Products: '.App\Models\Product::count()."\n";
echo 'Total Brands: '.App\Models\Brand::count()."\n";
echo 'Total Categories: '.App\Models\Category::count()."\n\n";

echo "=== Sample Products ===\n";
foreach ($products as $product) {
    echo "---------------------------------\n";
    echo "ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Brand: {$product->brand->name}\n";
    echo "Category: {$product->category->name}\n";
    echo "Slug: {$product->slug}\n";
    echo 'Active: '.($product->is_active ? 'Yes' : 'No')."\n";

    $attrs = $product->attributes_json;
    if ($attrs) {
        echo "Attributes:\n";
        if (isset($attrs['notes'])) {
            echo '  - Notes: '.json_encode($attrs['notes'])."\n";
        }
        if (isset($attrs['gender'])) {
            echo "  - Gender: {$attrs['gender']}\n";
        }
    }

    if ($product->variants->count() > 0) {
        echo "Variants:\n";
        foreach ($product->variants as $variant) {
            $inventory = $variant->inventory;
            echo "  - SKU: {$variant->sku}, Price: ¥{$variant->price_yen}, Stock: ".($inventory?->stock ?? 0)."\n";
        }
    }
    echo "\n";
}

echo "=== Products with Attributes ===\n";
$withAttrs = App\Models\Product::whereNotNull('attributes_json')->count();
echo "Products with attributes_json: {$withAttrs}\n";

echo "\n=== Done ===\n";
