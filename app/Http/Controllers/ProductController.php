<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $startTime = microtime(true);
        
        $perPage = (int) $request->integer('per_page', 12);
        $q = trim((string) $request->string('q'));
        $brand = trim((string) $request->string('brand'));
        $category = trim((string) $request->string('category'));
        $sort = trim((string) $request->string('sort'));
        $priceMin = $request->has('price_min') ? (int) $request->integer('price_min') : null;
        $priceMax = $request->has('price_max') ? (int) $request->integer('price_max') : null;
        $rating = $request->has('rating') ? (int) $request->integer('rating') : null;
        $gender = trim((string) $request->string('gender'));
        $size = $request->has('size') ? (int) $request->integer('size') : null;

        $mapProduct = function ($p) {
            /** @var \App\Models\Product $p */
            $imagePath = $p->heroImage?->path;
            $imageUrl  = $imagePath
                ? (str_starts_with($imagePath, 'http') || str_starts_with($imagePath, '/') ? $imagePath : Storage::url($imagePath))
                : null;

            $minPriceYen = (int) ($p->min_price_yen ?? 0);
            $minSaleYen  = $p->min_sale_price_yen !== null ? (int) $p->min_sale_price_yen : null;

            // Get variant options for gender and size display
            $variantOptions = $p->variants->where('is_active', true)->map(function ($variant) {
                return $variant->option_json;
            })->filter()->values();

            // Extract unique gender and size values
            $genders = $variantOptions->pluck('gender')->unique()->filter()->values();
            $sizes = $variantOptions->pluck('size_ml')->unique()->filter()->values();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => [
                    'name' => $p->brand?->name,
                    'slug' => $p->brand?->slug,
                ],
                'image' => $imageUrl,
                'price_cents' => $minSaleYen !== null ? $minSaleYen * 100 : ($minPriceYen * 100),
                'compare_at_cents' => $minSaleYen !== null ? ($minPriceYen * 100) : null,
                'average_rating' => round($p->averageRating() ?? 0, 1),
                'review_count' => $p->reviewCount(),
                'genders' => $genders,
                'sizes' => $sizes,
            ];
        };

        $base = Product::query()
            ->where('is_active', 1)
            ->select(['products.id', 'products.name', 'products.slug', 'products.brand_id', 'products.created_at'])
            ->with([
                'brand:id,name,slug',
                'heroImage:id,product_id,path,alt,rank',
            ])
            ->addSelect([
                'min_price_yen' => ProductVariant::query()
                    ->selectRaw('MIN(price_yen)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_active', 1),
                'min_sale_price_yen' => ProductVariant::query()
                    ->selectRaw('MIN(sale_price_yen)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->whereNotNull('sale_price_yen')
                    ->where('is_active', 1),
            ]);

        // Reusable DB filter applicator
        $applyFilters = function ($query) use ($brand, $category, $priceMin, $priceMax, $rating, $gender, $size) {
            if ($brand !== '') {
                $query->whereHas('brand', function ($b) use ($brand) {
                    $b->where('slug', $brand);
                });
            }
            if ($category !== '') {
                $query->whereHas('category', function ($c) use ($category) {
                    $c->where('slug', $category);
                });
            }
            if ($priceMin !== null || $priceMax !== null) {
                $min = $priceMin !== null ? $priceMin : 0;
                $max = $priceMax !== null ? $priceMax : PHP_INT_MAX;
                $query->havingRaw('(COALESCE(min_sale_price_yen, min_price_yen)) BETWEEN ? AND ?', [$min, $max]);
            }
            // Rating filter
            if ($rating !== null) {
                $query->whereHas('reviews', function ($r) use ($rating) {
                    $r->where('rating', '>=', $rating);
                });
            }
            // Gender filter (from variant options)
            if ($gender !== '') {
                $query->whereHas('variants', function ($v) use ($gender) {
                    $v->where('is_active', 1)
                      ->whereRaw("JSON_EXTRACT(option_json, '$.gender') = ?", [$gender]);
                });
            }
            // Size filter (from variant options)
            if ($size !== null) {
                $query->whereHas('variants', function ($v) use ($size) {
                    $v->where('is_active', 1)
                      ->whereRaw("JSON_EXTRACT(option_json, '$.size_ml') = ?", [$size]);
                });
            }
        };

        // If q present, try Scout search first; else DB fallback.
        if ($q !== '') {
            try {
                if (config('scout.driver')) {
                    $ids = Product::search($q)->keys()->all();

                    if (!empty($ids)) {
                        $query = (clone $base)->whereIn('products.id', $ids);
                        $applyFilters($query);
                        // Apply sort: keep relevance only when sort is empty
                        $this->applyOrder($query, $sort, $ids);

                        $products = Cache::remember($this->makeCacheKey($request), 30, function () use ($query, $perPage, $mapProduct) {
                            return $query->paginate($perPage)->through($mapProduct)->toArray();
                        });
                        $facets = $this->buildFacets((clone $query), $brand, $category, $priceMin, $priceMax);

                        // Log performance
                        $endTime = microtime(true);
                        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
                        
                        if ($duration > 1000) { // Log slow queries (> 1 second)
                            \Illuminate\Support\Facades\Log::warning('Slow search query', [
                                'duration_ms' => $duration,
                                'query' => $q,
                                'filters' => compact('brand', 'category', 'priceMin', 'priceMax', 'rating', 'gender', 'size'),
                                'user_id' => auth()->id(),
                            ]);
                        }

                        return Inertia::render('Products/Index', [
                            'products' => $products,
                            'filters'  => $request->only(['q', 'category', 'brand', 'sort', 'price_min', 'price_max', 'rating', 'gender', 'size']),
                            'facets'   => $facets,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // fall through to DB fallback
            }

            // DB fallback: simple LIKE search on name/desc
            $query = (clone $base)->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('short_desc', 'like', "%{$q}%")
                    ->orWhere('long_desc', 'like', "%{$q}%");
            });
            $applyFilters($query);
            $this->applyOrder($query, $sort, null);
            $products = Cache::remember($this->makeCacheKey($request), 30, function () use ($query, $perPage, $mapProduct) {
                return $query->paginate($perPage)->through($mapProduct)->toArray();
            });
            $facets = $this->buildFacets((clone $query), $brand, $category, $priceMin, $priceMax);

            // Log performance
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            if ($duration > 1000) { // Log slow queries (> 1 second)
                \Illuminate\Support\Facades\Log::warning('Slow search query', [
                    'duration_ms' => $duration,
                    'query' => $q,
                    'filters' => compact('brand', 'category', 'priceMin', 'priceMax', 'rating', 'gender', 'size'),
                    'user_id' => auth()->id(),
                ]);
            }

            return Inertia::render('Products/Index', [
                'products' => $products,
                'filters'  => $request->only(['q', 'category', 'brand', 'sort', 'price_min', 'price_max', 'rating', 'gender', 'size']),
                'facets'   => $facets,
            ]);
        }

        // Default listing (no q): newest first
        $query = (clone $base);
        $applyFilters($query);
        $this->applyOrder($query, $sort, null);
        $products = Cache::remember($this->makeCacheKey($request), 30, function () use ($query, $perPage, $mapProduct) {
            return $query->paginate($perPage)->through($mapProduct)->toArray();
        });

        $facets = $this->buildFacets((clone $query), $brand, $category, $priceMin, $priceMax);

        // Log performance
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        if ($duration > 1000) { // Log slow queries (> 1 second)
            \Illuminate\Support\Facades\Log::warning('Slow search query', [
                'duration_ms' => $duration,
                'query' => $q,
                'filters' => compact('brand', 'category', 'priceMin', 'priceMax', 'rating', 'gender', 'size'),
                'user_id' => auth()->id(),
            ]);
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters'  => $request->only(['q', 'category', 'brand', 'sort', 'price_min', 'price_max', 'rating', 'gender', 'size']),
            'facets'   => $facets,
        ]);
    }

    private function makeCacheKey(Request $request): string
    {
        $params = [
            'q' => (string) $request->query('q', ''),
            'brand' => (string) $request->query('brand', ''),
            'category' => (string) $request->query('category', ''),
            'price_min' => (string) $request->query('price_min', ''),
            'price_max' => (string) $request->query('price_max', ''),
            'rating' => (string) $request->query('rating', ''),
            'gender' => (string) $request->query('gender', ''),
            'size' => (string) $request->query('size', ''),
            'sort' => (string) $request->query('sort', ''),
            'per_page' => (string) $request->query('per_page', '12'),
            'page' => (string) $request->query('page', '1'),
        ];
        return 'products.index:' . sha1(json_encode($params));
    }

    private function applyOrder($query, string $sort, ?array $relevanceIds): void
    {
        // Default newest
        if ($sort === '' || $sort === 'newest') {
            // If we have relevance ids and no explicit sort, keep relevance order
            if ($relevanceIds && $sort === '') {
                $orderList = implode(',', array_map('intval', $relevanceIds));
                $query->orderByRaw("FIELD(products.id, {$orderList})");
            } else {
                $query->orderByDesc('products.created_at');
            }
            return;
        }

        // Price sort uses effective price (min_sale or min)
        if ($sort === 'price_asc') {
            $expr = 'COALESCE(min_sale_price_yen, min_price_yen)';
            // Emulate NULLS LAST for MySQL by ordering by nullness first
            $query->orderByRaw("({$expr}) IS NULL asc")
                ->orderByRaw("{$expr} asc")
                ->orderByDesc('products.created_at');
            return;
        }

        if ($sort === 'price_desc') {
            $expr = 'COALESCE(min_sale_price_yen, min_price_yen)';
            $query->orderByRaw("({$expr}) IS NULL asc")
                ->orderByRaw("{$expr} desc")
                ->orderByDesc('products.created_at');
            return;
        }

        // Fallback
        $query->orderByDesc('products.created_at');
    }

    private function buildFacets($query, string $brand, string $category, ?int $priceMin, ?int $priceMax): array
    {
        // Create a cache key for the facets
        $cacheKey = 'product_facets_' . md5(serialize([
            'brand' => $brand,
            'category' => $category,
            'price_min' => $priceMin,
            'price_max' => $priceMax
        ]));
        
        // Return cached facets if available
                return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function() use ($brand, $category, $priceMin, $priceMax) {
            // Build an effective price subselect to avoid ONLY_FULL_GROUP_BY issues with HAVING
            $effectivePriceSubselect = '(
                SELECT COALESCE(MIN(sale_price_yen), MIN(price_yen))
                FROM product_variants
                WHERE product_variants.product_id = products.id
                  AND product_variants.is_active = 1
            )';

            // Brand facet (ignore brand filter). Start fresh to avoid inherited selects.
            $brandQuery = \App\Models\Product::query()
                ->from('products')
                ->where('products.is_active', 1)
                ->join('brands', 'brands.id', '=', 'products.brand_id')
                ->when($category !== '', function ($q) use ($category) {
                    $q->whereHas('category', function ($c) use ($category) {
                        $c->where('slug', $category);
                    });
                })
                ->when($priceMin !== null || $priceMax !== null, function ($q) use ($effectivePriceSubselect, $priceMin, $priceMax) {
                    $min = $priceMin !== null ? $priceMin : 0;
                    $max = $priceMax !== null ? $priceMax : PHP_INT_MAX;
                    $q->whereRaw("{$effectivePriceSubselect} BETWEEN ? AND ?", [$min, $max]);
                })
                ->groupBy('brands.slug', 'brands.name')
                ->orderBy('brands.name')
                ->selectRaw('brands.slug as slug, brands.name as name, COUNT(products.id) as count');

            $brands = $brandQuery->get()->map(function ($r) use ($brand) {
                return [
                    'slug' => $r->slug,
                    'name' => $r->name,
                    'count' => (int) ($r->count ?? 0),
                    'active' => $brand === $r->slug,
                ];
            })->values();

            // Category facet (ignore category filter). Start fresh similarly.
            $categoryQuery = \App\Models\Product::query()
                ->from('products')
                ->where('products.is_active', 1)
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->when($brand !== '', function ($q) use ($brand) {
                    $q->whereHas('brand', function ($b) use ($brand) {
                        $b->where('slug', $brand);
                    });
                })
                ->when($priceMin !== null || $priceMax !== null, function ($q) use ($effectivePriceSubselect, $priceMin, $priceMax) {
                    $min = $priceMin !== null ? $priceMin : 0;
                    $max = $priceMax !== null ? $priceMax : PHP_INT_MAX;
                    $q->whereRaw("{$effectivePriceSubselect} BETWEEN ? AND ?", [$min, $max]);
                })
                ->groupBy('categories.slug', 'categories.name', 'categories.parent_id', 'categories.depth')
                ->orderBy('categories.name')
                ->selectRaw('categories.slug as slug, categories.name as name, categories.parent_id as parent_id, categories.depth as depth, COUNT(products.id) as count');

            $categories = $categoryQuery->get()->map(function ($r) use ($category) {
                return [
                    'slug' => $r->slug,
                    'name' => $r->name,
                    'count' => (int) ($r->count ?? 0),
                    'active' => $category === $r->slug,
                    'parent_id' => $r->parent_id ?? null,
                    'depth' => $r->depth ?? 0,
                ];
            })->values();

            // Price buckets facet
            $buckets = [
                ['label' => 'Under ¥3,000', 'min' => 0, 'max' => 3000],
                ['label' => '¥3,000 – ¥7,000', 'min' => 3000, 'max' => 7000],
                ['label' => '¥7,000 – ¥15,000', 'min' => 7000, 'max' => 15000],
                ['label' => '¥15,000+', 'min' => 15000, 'max' => null],
            ];

            $prices = collect($buckets)->map(function ($bucket) use ($brand, $category, $effectivePriceSubselect) {
                $q = \App\Models\Product::query()
                    ->from('products')
                    ->where('products.is_active', 1)
                    ->when($brand !== '', function ($qq) use ($brand) {
                        $qq->whereHas('brand', function ($b) use ($brand) {
                            $b->where('slug', $brand);
                        });
                    })
                    ->when($category !== '', function ($qq) use ($category) {
                        $qq->whereHas('category', function ($c) use ($category) {
                            $c->where('slug', $category);
                        });
                    });
                $min = $bucket['min'];
                $max = $bucket['max'] ?? PHP_INT_MAX;
                $q->whereRaw("{$effectivePriceSubselect} BETWEEN ? AND ?", [$min, $max]);
                $count = (clone $q)->count();
                return [
                    'label' => $bucket['label'],
                    'min' => $bucket['min'],
                    'max' => $bucket['max'],
                    'count' => (int) $count,
                    'active' => false,
                ];
            })->values();

            // Mark active price bucket if current filter matches
            if ($priceMin !== null || $priceMax !== null) {
                $prices = $prices->map(function ($p) use ($priceMin, $priceMax) {
                    $isActive = ($priceMin !== null && $p['min'] === $priceMin)
                        && ((($p['max'] ?? null) === null && $priceMax === null) || ($p['max'] ?? null) === $priceMax);
                    $p['active'] = $isActive;
                    return $p;
                })->values();
            }

            // Rating facets
            $ratings = collect([5, 4, 3, 2, 1])->map(function ($rating) use ($brand, $category, $priceMin, $priceMax, $effectivePriceSubselect) {
                $q = \App\Models\Product::query()
                    ->from('products')
                    ->where('products.is_active', 1)
                    ->whereHas('reviews', function ($r) use ($rating) {
                        $r->where('rating', '>=', $rating);
                    })
                    ->when($brand !== '', function ($qq) use ($brand) {
                        $qq->whereHas('brand', function ($b) use ($brand) {
                            $b->where('slug', $brand);
                        });
                    })
                    ->when($category !== '', function ($qq) use ($category) {
                        $qq->whereHas('category', function ($c) use ($category) {
                            $c->where('slug', $category);
                        });
                    })
                    ->when($priceMin !== null || $priceMax !== null, function ($q) use ($effectivePriceSubselect, $priceMin, $priceMax) {
                        $min = $priceMin !== null ? $priceMin : 0;
                        $max = $priceMax !== null ? $priceMax : PHP_INT_MAX;
                        $q->whereRaw("{$effectivePriceSubselect} BETWEEN ? AND ?", [$min, $max]);
                    });
                
                $count = (clone $q)->count();
                
                return [
                    'rating' => $rating,
                    'count' => (int) $count,
                    'label' => str_repeat('★', $rating) . str_repeat('☆', 5 - $rating),
                    'active' => false, // Will be set based on current filter
                ];
            })->filter(function ($r) {
                return $r['count'] > 0; // Only show ratings that have products
            })->values();

            return [
                'brands' => $brands,
                'categories' => $categories,
                'prices' => $prices,
                'ratings' => $ratings,
            ];
        });
    }

    public function show(Product $product)
    {
        $product->load([
            'brand:id,name,slug',
            'images' => function ($q) {
                $q->select(['id', 'product_id', 'path', 'alt', 'rank', 'is_hero'])->orderBy('is_hero', 'desc')->orderBy('rank');
            },
            'variants' => function ($q) {
                $q->select(['id', 'product_id', 'sku', 'option_json', 'price_yen', 'sale_price_yen', 'is_active']);
            },
            'variants.inventory:id,product_variant_id,stock,safety_stock,managed',
            'category:id,name,slug',
        ]);

        $hero = $product->heroImage;
        $imageUrl = $hero?->path ? (str_starts_with($hero->path, 'http') || str_starts_with($hero->path, '/') ? $hero->path : Storage::url($hero->path)) : null;

        $variants = $product->variants->where('is_active', true)->map(function ($v) {
            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'price_cents' => $v->sale_price_yen !== null ? (int) $v->sale_price_yen * 100 : (int) $v->price_yen * 100,
                'compare_at_cents' => $v->sale_price_yen !== null ? (int) $v->price_yen * 100 : null,
                'stock' => $v->inventory?->stock,
                'safety_stock' => $v->inventory?->safety_stock,
                'managed' => (bool) ($v->inventory?->managed ?? false),
                'options' => $v->option_json, // Include variant options
            ];
        })->values();

        $gallery = $product->images->map(fn($img) => [
            'url'     => $img->url, // adapt to your accessor/column
            'alt'     => $product->name,
            'is_hero' => (bool)$img->is_hero,
        ])->values();

        // Related products: same brand or category, exclude current, limit 8
        $relatedQuery = Product::query()
            ->where('is_active', 1)
            ->where('id', '!=', $product->id)
            ->when($product->brand_id, fn($q) => $q->where('brand_id', $product->brand_id))
            ->orWhere(function ($q) use ($product) {
                $q->where('is_active', 1)
                    ->where('id', '!=', $product->id)
                    ->where('category_id', $product->category_id);
            })
            ->select(['id', 'name', 'slug', 'brand_id', 'created_at'])
            ->with(['brand:id,name,slug', 'heroImage:id,product_id,path,alt,rank'])
            ->addSelect([
                'min_price_yen' => ProductVariant::query()
                    ->selectRaw('MIN(price_yen)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_active', 1),
                'min_sale_price_yen' => ProductVariant::query()
                    ->selectRaw('MIN(sale_price_yen)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->whereNotNull('sale_price_yen')
                    ->where('is_active', 1),
            ])
            ->limit(8)
            ->get()
            ->map(function ($p) {
                /** @var \App\Models\Product $p */
                $imagePath = $p->heroImage?->path;
                $imageUrl  = $imagePath ? (str_starts_with($imagePath, 'http') || str_starts_with($imagePath, '/') ? $imagePath : Storage::url($imagePath)) : null;
                $minPriceYen = (int) ($p->min_price_yen ?? 0);
                $minSaleYen  = $p->min_sale_price_yen !== null ? (int) $p->min_sale_price_yen : null;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'brand' => $p->brand?->name,
                    'price_cents' => $minSaleYen !== null ? $minSaleYen * 100 : ($minPriceYen * 100),
                    'compare_at_cents' => $minSaleYen !== null ? ($minPriceYen * 100) : null,
                    'image' => $imageUrl,
                    'average_rating' => round($p->averageRating() ?? 0, 1),
                    'review_count' => $p->reviewCount(),
                ];
            });

        return Inertia::render('Products/Show', [
            /** @var \App\Models\Product $product */
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => [
                    'name' => $product->brand?->name,
                    'slug' => $product->brand?->slug,
                ],
                'image' => $imageUrl,
                'short_desc' => $product->short_desc,
                'long_desc' => $product->long_desc,
                'variants' => $variants,
                'average_rating' => round($product->averageRating() ?? 0, 1),
                'review_count' => $product->reviewCount(),
            ],
            'gallery' => $product->images->map(function ($img) {
                $url = $img->path ? (str_starts_with($img->path, 'http') || str_starts_with($img->path, '/') ? $img->path : Storage::url($img->path)) : null;
                return ['url' => $url, 'alt' => $img->alt, 'is_hero' => (bool) $img->is_hero];
            })->values(),
            'related' => $relatedQuery,
        ]);
    }
}
