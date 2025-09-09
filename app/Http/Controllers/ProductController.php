<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 12);

        $products = Product::query()
            ->where('is_active', 1)
            ->select(['id', 'name', 'slug', 'brand_id'])
            ->with([
                // keep these relations lean
                'brand:id,name,slug',
                'heroImage:id,product_id,path,alt,rank', // Stage 3: single hero enforced
            ])
            // compute min active variant price
            ->addSelect([
                'min_price_cents' => ProductVariant::query()
                    ->selectRaw('MIN(price_cents)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_active', 1),
                'min_sale_price_cents' => ProductVariant::query()
                    ->selectRaw('MIN(sale_price_cents)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->whereNotNull('sale_price_cents')
                    ->where('is_active', 1),
            ])
            ->latest('products.created_at')
            ->paginate($perPage)
            ->through(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'brand' => [
                        'name' => optional($p->brand)->name,
                        'slug' => optional($p->brand)->slug,
                    ],
                    'image' => optional($p->heroImage)->path, // map in frontend to full URL
                    'price_cents' => $p->min_sale_price_cents ?? $p->min_price_cents,
                    'compare_at_cents' => $p->min_sale_price_cents ? $p->min_price_cents : null,
                ];
            });

        return Inertia::render('products/index', [
            'products' => $products,                  // { data, links, meta }
            'filters'  => $request->only(['q', 'category', 'brand', 'sort']),
            'facets'   => [],                         // 4.1.3
        ]);
    }
}
