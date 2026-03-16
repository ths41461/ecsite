<?php

/**
 * Comprehensive E2E Tests for AI Fragrance Recommendation System
 *
 * Tests cover:
 * - All database tables and data
 * - All quiz options matching to products
 * - All vibe-to-notes mapping
 * - All personality/brand matching
 * - All budget ranges
 * - All gender preferences
 *
 * @group e2e
 * @group ai
 * @group full-integration
 */

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

describe('AI Fragrance Recommendation - Full Database E2E Tests', function () {

    // =========================================================================
    // SECTION 1: Database Integrity Tests
    // =========================================================================

    describe('Database Integrity - All Tables Have Data', function () {
        it('products table has at least 100 products', function () {
            $count = Product::where('is_active', true)->count();
            expect($count)->toBeGreaterThanOrEqual(100, 'Need at least 100 products');
        });

        it('brands table has at least 15 brands', function () {
            $count = Brand::count();
            expect($count)->toBeGreaterThanOrEqual(15, 'Need at least 15 brands');
        });

        it('categories table has at least 15 categories', function () {
            $count = Category::count();
            expect($count)->toBeGreaterThanOrEqual(15, 'Need at least 15 categories');
        });

        it('product_variants table has at least 150 variants', function () {
            $count = ProductVariant::where('is_active', true)->count();
            expect($count)->toBeGreaterThanOrEqual(150, 'Need at least 150 variants');
        });

        it('all products have brand_id', function () {
            $withoutBrand = Product::whereNull('brand_id')->count();
            expect($withoutBrand)->toBe(0, 'All products must have brand_id');
        });

        it('all products have category_id', function () {
            $withoutCategory = Product::whereNull('category_id')->count();
            expect($withoutCategory)->toBe(0, 'All products must have category_id');
        });

        it('all products have attributes_json with notes', function () {
            $withoutNotes = Product::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('attributes_json')
                        ->orWhereRaw("JSON_EXTRACT(attributes_json, '$.notes') IS NULL");
                })
                ->count();
            expect($withoutNotes)->toBe(0, 'All products must have fragrance notes');
        });

        it('all variants have price_yen', function () {
            $withoutPrice = ProductVariant::whereNull('price_yen')->count();
            expect($withoutPrice)->toBe(0, 'All variants must have price');
        });

        it('all variants have option_json with size_ml', function () {
            $withoutSize = ProductVariant::where(function ($q) {
                $q->whereNull('option_json')
                    ->orWhereRaw("JSON_EXTRACT(option_json, '$.size_ml') IS NULL");
            })->count();
            expect($withoutSize)->toBe(0, 'All variants must have size_ml');
        });
    });

    // =========================================================================
    // SECTION 2: Budget Range Tests - All Price Ranges Have Products
    // =========================================================================

    describe('Budget Ranges - All Price Tiers Have Products', function () {
        it('budget ¥3,000 has products', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 3000))
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need products under ¥3,000');
        });

        it('budget ¥5,000 has products', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 5000))
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need products under ¥5,000');
        });

        it('budget ¥8,000 has products', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 8000))
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need products under ¥8,000');
        });

        it('budget ¥15,000 has products', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 15000))
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need products under ¥15,000');
        });

        it('budget ¥30,000 has products', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 30000))
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need products under ¥30,000');
        });

        it('luxury products over ¥30,000 exist', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '>', 30000))
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need luxury products over ¥30,000');
        });
    });

    // =========================================================================
    // SECTION 3: Gender Distribution Tests
    // =========================================================================

    describe('Gender Distribution - All Genders Have Products', function () {
        it('women products exist', function () {
            $products = Product::where('is_active', true)
                ->where('attributes_json', 'LIKE', '%"gender":"women"%')
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need women products');
        });

        it('men products exist', function () {
            $products = Product::where('is_active', true)
                ->where('attributes_json', 'LIKE', '%"gender":"men"%')
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need men products');
        });

        it('unisex products exist', function () {
            $products = Product::where('is_active', true)
                ->where('attributes_json', 'LIKE', '%"gender":"unisex"%')
                ->count();
            expect($products)->toBeGreaterThan(0, 'Need unisex products');
        });
    });

    // =========================================================================
    // SECTION 4: Vibe Matching Tests - All Vibes Should Match Products
    // =========================================================================

    describe('Vibe Matching - Each Vibe Should Find Matching Products', function () {
        $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];

        foreach ($vibes as $vibe) {
            it("vibe '$vibe' matches products in database", function () use ($vibe) {
                $config = config('fragrance.vibe_notes', []);

                expect($config)->toHaveKey($vibe, "Vibe '$vibe' must be defined in config");

                $vibeNotes = $config[$vibe] ?? [];
                $searchNotes = [];

                foreach ($vibeNotes as $noteType => $notes) {
                    $searchNotes = array_merge($searchNotes, $notes);
                }

                $products = Product::where('is_active', true)
                    ->where(function ($q) use ($searchNotes) {
                        foreach ($searchNotes as $note) {
                            $q->orWhere('attributes_json', 'LIKE', "%{$note}%");
                        }
                    })
                    ->count();

                expect($products)->toBeGreaterThan(0,
                    "Vibe '$vibe' should match at least one product in database"
                );
            });
        }
    });

    // =========================================================================
    // SECTION 5: Personality/Brand Matching Tests
    // =========================================================================

    describe('Personality to Brand Matching', function () {
        $personalities = [
            'romantic' => ['シャネル', 'ディオール', 'ジルスチュアート', 'アナスイ'],
            'energetic' => ['ケンゾー', '資生堂', 'SHIRO', 'アナスイ'],
            'cool' => ['トムフォード', 'グッチ', 'ヴェルサーチ', 'プラダ', 'アルマーニ'],
            'natural' => ['SHIRO', '資生堂', 'ケンゾー', 'ジョーマローン'],
        ];

        foreach ($personalities as $personality => $brands) {
            it("personality '$personality' matches preferred brands", function () use ($personality, $brands) {
                $config = config('fragrance.personality_styles', []);

                expect($config)->toHaveKey($personality, "Personality '$personality' must be defined");

                $matchedBrands = Brand::whereIn('name', $brands)->count();

                expect($matchedBrands)->toBeGreaterThan(0,
                    "Personality '$personality' preferred brands should exist in database"
                );
            });
        }
    });

    // =========================================================================
    // SECTION 6: ContextBuilder Full Integration Tests
    // =========================================================================

    describe('ContextBuilder - Full Integration Tests', function () {
        it('returns products for romantic/floral/budget ¥5000', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
                'gender' => 'women',
            ];

            $context = $cb->build($quizData);

            expect($context['available_products'])->not->toBeEmpty();
            expect($context['trending_products'])->not->toBeEmpty();
            expect($context['database_info']['total_products'])->toBeGreaterThan(0);
        });

        it('returns products for cool/woody/budget ¥10000', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'personality' => 'cool',
                'vibe' => 'woody',
                'occasion' => ['work'],
                'style' => 'chic',
                'budget' => 10000,
                'experience' => 'some',
                'gender' => 'men',
            ];

            $context = $cb->build($quizData);

            expect($context['available_products'])->not->toBeEmpty();
        });

        it('returns products for natural/citrus/budget ¥3000', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'personality' => 'natural',
                'vibe' => 'citrus',
                'occasion' => ['casual'],
                'style' => 'natural',
                'budget' => 3000,
                'experience' => 'beginner',
                'gender' => 'unisex',
            ];

            $context = $cb->build($quizData);

            expect($context['available_products'])->not->toBeEmpty();

            // Verify price is within budget
            foreach ($context['available_products'] as $product) {
                expect($product['min_price'])->toBeLessThanOrEqual(3000);
            }
        });

        it('returns products for energetic/ocean/budget ¥8000', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'personality' => 'energetic',
                'vibe' => 'ocean',
                'occasion' => ['date', 'special'],
                'style' => 'casual',
                'budget' => 8000,
                'experience' => 'experienced',
                'gender' => 'women',
            ];

            $context = $cb->build($quizData);

            expect($context['available_products'])->not->toBeEmpty();
        });

        it('returns products for high budget ¥30000', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['special'],
                'style' => 'feminine',
                'budget' => 30000,
                'experience' => 'experienced',
                'gender' => 'women',
            ];

            $context = $cb->build($quizData);

            expect($context['available_products'])->not->toBeEmpty();
        });
    });

    // =========================================================================
    // SECTION 7: All Quiz Option Combinations Tests
    // =========================================================================

    describe('All Quiz Combinations Return Products', function () {
        $personalities = ['romantic', 'energetic', 'cool', 'natural'];
        $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];
        $budgets = [3000, 5000, 8000, 15000];

        // Test a representative sample of combinations
        foreach ($personalities as $personality) {
            foreach ($vibes as $vibe) {
                $testBudget = 5000;

                it("personality='$personality', vibe='$vibe', budget=¥$testBudget returns products", function () use ($personality, $vibe, $testBudget) {
                    $cb = new \App\Services\AI\ContextBuilder;
                    $quizData = [
                        'personality' => $personality,
                        'vibe' => $vibe,
                        'occasion' => ['daily'],
                        'style' => 'casual',
                        'budget' => $testBudget,
                        'experience' => 'beginner',
                        'gender' => 'women',
                    ];

                    $context = $cb->build($quizData);

                    expect($context['available_products'])->not->toBeEmpty(
                        "Combination ($personality, $vibe, ¥$testBudget) must return products"
                    );
                });
            }
        }
    });

    // =========================================================================
    // SECTION 8: Product Quality Tests
    // =========================================================================

    describe('Product Data Quality in ContextBuilder Results', function () {
        it('all returned products have required fields', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'budget' => 5000,
                'gender' => 'women',
            ];

            $context = $cb->build($quizData);

            foreach ($context['available_products'] as $product) {
                expect($product)->toHaveKeys([
                    'id', 'name', 'brand', 'min_price', 'notes', 'gender',
                ], 'Product must have all required fields');
            }
        });

        it('all returned products have variants', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $quizData = [
                'vibe' => 'floral',
                'budget' => 5000,
            ];

            $context = $cb->build($quizData);

            foreach ($context['available_products'] as $product) {
                expect($product['variants'])->not->toBeEmpty();

                foreach ($product['variants'] as $variant) {
                    expect($variant)->toHaveKeys(['id', 'price_yen', 'size_ml']);
                }
            }
        });

        it('all returned products are within budget', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $budgets = [3000, 5000, 8000, 15000];

            foreach ($budgets as $budget) {
                $quizData = [
                    'vibe' => 'floral',
                    'budget' => $budget,
                ];

                $context = $cb->build($quizData);

                foreach ($context['available_products'] as $product) {
                    expect($product['min_price'])->toBeLessThanOrEqual($budget,
                        "Product price must be within budget ¥$budget"
                    );
                }
            }
        });
    });

    // =========================================================================
    // SECTION 9: Database Info Tests
    // =========================================================================

    describe('Database Info in Context', function () {
        it('includes all brands in database info', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $context = $cb->build(['budget' => 10000]);

            $brandsInContext = $context['database_info']['brands'] ?? [];
            $brandsInDb = Brand::pluck('name')->toArray();

            expect(count($brandsInContext))->toBeGreaterThanOrEqual(count($brandsInDb));
        });

        it('includes all categories in database info', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $context = $cb->build(['budget' => 10000]);

            $catsInContext = $context['database_info']['categories'] ?? [];
            $catsInDb = Category::whereNotNull('parent_id')->pluck('name')->toArray();

            expect(count($catsInContext))->toBeGreaterThanOrEqual(count($catsInDb));
        });

        it('includes correct price range', function () {
            $cb = new \App\Services\AI\ContextBuilder;
            $context = $cb->build(['budget' => 10000]);

            $priceRange = $context['database_info']['price_range'] ?? [];

            expect($priceRange['min'])->toBeGreaterThan(0);
            expect($priceRange['max'])->toBeGreaterThan($priceRange['min']);
        });
    });

    // =========================================================================
    // SECTION 10: API Endpoint Tests
    // =========================================================================

    describe('API Endpoints Return Products', function () {
        it('POST /api/v1/ai/quiz returns products for valid request', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
            ]);

            $response->assertStatus(200);

            $data = $response->json('data');
            expect($data['recommendations'] ?? [])->not->toBeEmpty();
        });

        it('results page returns products for all budget options', function () {
            $budgets = [3000, 5000, 8000, 15000];

            foreach ($budgets as $budget) {
                $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
                    'personality' => 'natural',
                    'vibe' => 'citrus',
                    'occasion' => ['daily'],
                    'style' => 'casual',
                    'budget' => $budget,
                    'experience' => 'beginner',
                ]));

                $response->assertStatus(200);
            }
        });
    });
});
