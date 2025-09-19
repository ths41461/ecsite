<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartCouponFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Swap Redis with an in-memory implementation so the cart meta store behaves predictably in tests.
        Redis::swap(new class {
            private array $store = [];

            public function get(string $key): mixed
            {
                return $this->store[$key] ?? null;
            }

            public function setex(string $key, int $ttl, mixed $value): bool
            {
                $this->store[$key] = $value;
                return true;
            }

            public function del(string|array $keys): int
            {
                $count = 0;
                foreach ((array) $keys as $key) {
                    if (array_key_exists($key, $this->store)) {
                        unset($this->store[$key]);
                        $count++;
                    }
                }

                return $count;
            }

            public function flushall(): void
            {
                $this->store = [];
            }

            public function connection($name = null): self
            {
                return $this;
            }
        });
    }

    public function test_coupon_only_affects_lines_present_when_entered_and_can_be_removed(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $productA = Product::create([
            'name' => 'Test Product A',
            'slug' => 'test-product-a-' . Str::random(6),
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'name' => 'Test Product B',
            'slug' => 'test-product-b-' . Str::random(6),
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $variantA = ProductVariant::create([
            'product_id' => $productA->id,
            'sku' => 'SKU-' . Str::upper(Str::random(8)),
            'price_yen' => 1200,
            'sale_price_yen' => null,
            'is_active' => true,
        ]);

        $variantB = ProductVariant::create([
            'product_id' => $productB->id,
            'sku' => 'SKU-' . Str::upper(Str::random(8)),
            'price_yen' => 2000,
            'sale_price_yen' => null,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variantA->id,
            'stock' => 10,
            'safety_stock' => 0,
            'managed' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variantB->id,
            'stock' => 10,
            'safety_stock' => 0,
            'managed' => true,
        ]);

        $coupon = Coupon::create([
            'code' => 'SALE11',
            'description' => '9% off',
            'type' => 'percent',
            'value' => 9,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'max_uses' => null,
            'max_uses_per_user' => null,
            'min_subtotal_yen' => null,
            'max_discount_yen' => null,
            'exclude_sale_items' => false,
            'is_active' => true,
        ]);

        DB::table('coupon_products')->insert([
            'coupon_id' => $coupon->id,
            'product_id' => $productA->id,
        ]);

        DB::table('coupon_products')->insert([
            'coupon_id' => $coupon->id,
            'product_id' => $productB->id,
        ]);

        $cartResponse = $this
            ->withSession([])
            ->postJson('/cart', ['variant_id' => $variantA->id, 'qty' => 1]);

        $cartResponse
            ->assertOk()
            ->assertJsonPath('lines.0.variant_id', $variantA->id);

        $sessionId = session()->getId();

        $applyResponse = $this
            ->withSession([])
            ->postJson('/cart/coupon', ['code' => 'SALE11']);

        $applyResponse
            ->assertOk()
            ->assertJsonPath('coupon_code', 'SALE11')
            ->assertJsonPath('coupon_discount_cents', fn ($value) => $value > 0);

        $initialDiscount = $applyResponse->json('coupon_discount_cents');

        // Add another product after the coupon has been applied.
        $secondAdd = $this
            ->withSession([])
            ->postJson('/cart', ['variant_id' => $variantB->id, 'qty' => 1]);

        $secondAdd
            ->assertOk()
            ->assertJsonPath('coupon_code', 'SALE11')
            ->assertJsonPath('coupon_discount_cents', $initialDiscount);

        // Reapply the coupon so the new line becomes eligible.
        $reapplyResponse = $this
            ->withSession([])
            ->postJson('/cart/coupon', ['code' => 'SALE11']);

        $reapplyResponse
            ->assertOk()
            ->assertJsonPath('coupon_code', 'SALE11')
            ->assertJsonPath('coupon_discount_cents', fn ($value) => $value > $initialDiscount);

        $previewResponse = $this
            ->withSession([])
            ->postJson('/cart/coupon/preview', ['code' => 'SALE11']);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('discount_cents', fn ($value) => $value > 0);

        $this->assertNotNull(Redis::get('cartmeta:' . $sessionId));

        $removeResponse = $this
            ->withSession([])
            ->deleteJson('/cart/coupon');

        $removeResponse
            ->assertOk()
            ->assertJsonPath('coupon_code', null)
            ->assertJsonPath('coupon_discount_cents', 0);

        $this->assertNull(Redis::get('cartmeta:' . $sessionId));
    }
}
