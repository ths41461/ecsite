<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateDataStructures extends Command
{
    protected $signature = 'validate:data-structures';

    protected $description = 'Validate database structures match documentation';

    protected int $passed = 0;

    protected int $failed = 0;

    protected int $warnings = 0;

    public function handle(): int
    {
        $this->output->writeln('');
        $this->output->writeln('╔══════════════════════════════════════════════════════════════════════╗');
        $this->output->writeln('║     AI FRAGRANCE RECOMMENDATION AGENT - DATA VALIDATION              ║');
        $this->output->writeln('╚══════════════════════════════════════════════════════════════════════╝');
        $this->output->writeln('');

        $this->validateDatabaseConnection();
        $this->validateProductsTable();
        $this->validateInventoriesTable();
        $this->validateReviewsTable();
        $this->validateProductVariantsTable();
        $this->validateAiTables();
        $this->validateModelRelationships();
        $this->validateDataContent();
        $this->validateAttributesJson();
        $this->validateOptionJson();
        $this->validateRelationshipChain();
        $this->validateRedis();

        $this->printSummary();

        return $this->failed > 0 ? 1 : 0;
    }

    protected function test(string $name, bool $condition, string $message = ''): bool
    {
        if ($condition) {
            $this->output->writeln("<info>✅ PASS:</info> {$name}");
            $this->passed++;

            return true;
        }

        $output = "<error>❌ FAIL:</error> {$name}";
        if ($message) {
            $output .= " - {$message}";
        }
        $this->output->writeln($output);
        $this->failed++;

        return false;
    }

    protected function addWarning(string $message): void
    {
        $this->output->writeln("<comment>⚠️  WARN:</comment> {$message}");
        $this->warnings++;
    }

    protected function section(string $title): void
    {
        $this->output->writeln('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->output->writeln($title);
        $this->output->writeln('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->output->writeln('');
    }

    protected function validateDatabaseConnection(): void
    {
        $this->section('SECTION 1: DATABASE CONNECTION');

        $dbName = DB::connection()->getDatabaseName();
        $this->test('Database connection', $dbName === 'laravel', "Expected 'laravel', got '{$dbName}'");

        $tableCount = count(DB::select('SHOW TABLES'));
        $this->test('Tables exist', $tableCount > 0, 'No tables found');
        $this->output->writeln("   → Found {$tableCount} tables");
    }

    protected function validateProductsTable(): void
    {
        $this->section('SECTION 2: PRODUCTS TABLE');

        try {
            $columns = collect(DB::select('DESCRIBE products'))->pluck('Type', 'Field');

            $this->test('products.name column exists', isset($columns['name']));
            $this->test('products.name is varchar(120)', $columns['name'] === 'varchar(120)', "Got: {$columns['name']}");

            $this->test('products.attributes_json exists', isset($columns['attributes_json']));
            $this->test('products.attributes_json is json', str_contains($columns['attributes_json'], 'json'), "Got: {$columns['attributes_json']}");

            $this->test('products.meta_json exists', isset($columns['meta_json']));
            $this->test('products.brand_id exists', isset($columns['brand_id']));
            $this->test('products.category_id exists', isset($columns['category_id']));

            $this->test('products.top_notes does NOT exist', ! isset($columns['top_notes']), 'This column should NOT exist!');
            $this->test('products.middle_notes does NOT exist', ! isset($columns['middle_notes']), 'This column should NOT exist!');
            $this->test('products.base_notes does NOT exist', ! isset($columns['base_notes']), 'This column should NOT exist!');

            $this->output->writeln("\n   Full columns list:");
            foreach ($columns as $col => $type) {
                $this->output->writeln("   - {$col}: {$type}");
            }
        } catch (\Exception $e) {
            $this->test('Products table query', false, $e->getMessage());
        }
    }

    protected function validateInventoriesTable(): void
    {
        $this->section('SECTION 3: INVENTORIES TABLE');

        try {
            $columns = collect(DB::select('DESCRIBE inventories'))->pluck('Type', 'Field');

            $this->test('inventories.id exists', isset($columns['id']));
            $this->test('inventories.product_variant_id exists', isset($columns['product_variant_id']));
            $this->test('inventories.stock exists', isset($columns['stock']));
            $this->test('inventories.safety_stock exists', isset($columns['safety_stock']));
            $this->test('inventories.managed exists', isset($columns['managed']));

            $this->test('inventories.product_id does NOT exist', ! isset($columns['product_id']),
                'CRITICAL: Use product_variant_id instead');

            $this->test('inventories.quantity does NOT exist', ! isset($columns['quantity']),
                "Column is called 'stock', not 'quantity'");

            $this->output->writeln("\n   Full columns list:");
            foreach ($columns as $col => $type) {
                $this->output->writeln("   - {$col}: {$type}");
            }
        } catch (\Exception $e) {
            $this->test('Inventories table query', false, $e->getMessage());
        }
    }

    protected function validateReviewsTable(): void
    {
        $this->section('SECTION 4: REVIEWS TABLE');

        try {
            $columns = collect(DB::select('DESCRIBE reviews'))->pluck('Type', 'Field');

            $this->test('reviews.product_id exists', isset($columns['product_id']));
            $this->test('reviews.rating exists', isset($columns['rating']));
            $this->test('reviews.body exists', isset($columns['body']));

            $this->test('reviews.approved exists', isset($columns['approved']),
                "Column is 'approved', NOT 'is_approved'");

            $this->test('reviews.is_approved does NOT exist', ! isset($columns['is_approved']),
                "CRITICAL: Use 'approved', not 'is_approved'");

            $this->output->writeln("\n   Full columns list:");
            foreach ($columns as $col => $type) {
                $this->output->writeln("   - {$col}: {$type}");
            }
        } catch (\Exception $e) {
            $this->test('Reviews table query', false, $e->getMessage());
        }
    }

    protected function validateProductVariantsTable(): void
    {
        $this->section('SECTION 5: PRODUCT_VARIANTS TABLE');

        try {
            $columns = collect(DB::select('DESCRIBE product_variants'))->pluck('Type', 'Field');

            $this->test('product_variants.product_id exists', isset($columns['product_id']));
            $this->test('product_variants.sku exists', isset($columns['sku']));
            $this->test('product_variants.option_json exists', isset($columns['option_json']));
            $this->test('product_variants.price_yen exists', isset($columns['price_yen']));

            $this->output->writeln("\n   Full columns list:");
            foreach ($columns as $col => $type) {
                $this->output->writeln("   - {$col}: {$type}");
            }
        } catch (\Exception $e) {
            $this->test('Product_variants table query', false, $e->getMessage());
        }
    }

    protected function validateAiTables(): void
    {
        $this->section('SECTION 6: AI TABLES');

        foreach (['ai_chat_sessions', 'ai_messages', 'quiz_results', 'user_scent_profiles', 'ai_recommendation_cache'] as $table) {
            try {
                DB::select("DESCRIBE {$table}");
                $this->test("{$table} table exists", true);
            } catch (\Exception $e) {
                $this->test("{$table} table exists", false, $e->getMessage());
            }
        }

        try {
            $columns = collect(DB::select('DESCRIBE ai_chat_sessions'))->pluck('Type', 'Field');
            $this->test('ai_chat_sessions.session_token exists', isset($columns['session_token']));
            $this->test('ai_chat_sessions.context_json exists', isset($columns['context_json']));
        } catch (\Exception $e) {
        }

        try {
            $columns = collect(DB::select('DESCRIBE ai_messages'))->pluck('Type', 'Field');
            $this->test('ai_messages.session_id exists', isset($columns['session_id']));
            $this->test('ai_messages.role exists', isset($columns['role']));
            $this->test('ai_messages.content exists', isset($columns['content']));
        } catch (\Exception $e) {
        }

        try {
            $columns = collect(DB::select('DESCRIBE quiz_results'))->pluck('Type', 'Field');
            $this->test('quiz_results.session_token exists', isset($columns['session_token']));
            $this->test('quiz_results.answers_json exists', isset($columns['answers_json']));
        } catch (\Exception $e) {
        }
    }

    protected function validateModelRelationships(): void
    {
        $this->section('SECTION 7: MODEL RELATIONSHIPS');

        $productModel = file_get_contents(app_path('Models/Product.php'));
        $this->test('Product has brand() relationship', str_contains($productModel, 'function brand()'));
        $this->test('Product has category() relationship', str_contains($productModel, 'function category()'));
        $this->test('Product has variants() relationship', str_contains($productModel, 'function variants()'));
        $this->test('Product has reviews() relationship', str_contains($productModel, 'function reviews()'));

        if (str_contains($productModel, 'function inventory()')) {
            $this->addWarning('Product has inventory() relationship - this goes through variants!');
        }

        $inventoryModel = file_get_contents(app_path('Models/Inventory.php'));
        $this->test('Inventory belongs to variant', str_contains($inventoryModel, 'function variant()'));

        $variantModel = file_get_contents(app_path('Models/ProductVariant.php'));
        $this->test('ProductVariant belongs to product', str_contains($variantModel, 'function product()'));
        $this->test('ProductVariant has one inventory', str_contains($variantModel, 'function inventory()'));
    }

    protected function validateDataContent(): void
    {
        $this->section('SECTION 8: DATA CONTENT (Requires Seeding)');

        $productCount = Product::count();
        $brandCount = Brand::count();
        $categoryCount = Category::count();
        $variantCount = ProductVariant::count();
        $inventoryCount = Inventory::count();

        $this->test('Products table has data', $productCount > 0, "Run 'php artisan db:seed'");
        $this->test('Brands table has data', $brandCount > 0, "Run 'php artisan db:seed'");
        $this->test('Categories table has data', $categoryCount > 0, "Run 'php artisan db:seed'");

        if ($productCount > 0) {
            $this->output->writeln("\n   Data counts:");
            $this->output->writeln("   - Products: {$productCount}");
            $this->output->writeln("   - Brands: {$brandCount}");
            $this->output->writeln("   - Categories: {$categoryCount}");
            $this->output->writeln("   - Variants: {$variantCount}");
            $this->output->writeln("   - Inventories: {$inventoryCount}");
        } else {
            $this->addWarning('Database is empty! Run: php artisan db:seed');
        }
    }

    protected function validateAttributesJson(): void
    {
        $this->section('SECTION 9: ATTRIBUTES_JSON STRUCTURE');

        $product = Product::first();
        if (! $product) {
            $this->addWarning('No products to validate attributes_json');

            return;
        }

        $this->test('Product has attributes_json', ! empty($product->attributes_json));

        if (empty($product->attributes_json)) {
            return;
        }

        $attrs = $product->attributes_json;

        $this->test('attributes_json is array', is_array($attrs));
        $this->test("attributes_json has 'notes' key", isset($attrs['notes']));
        $this->test("attributes_json has 'gender' key", isset($attrs['gender']));

        if (isset($attrs['notes'])) {
            $this->test("notes has 'top' key", isset($attrs['notes']['top']));
            $this->test("notes has 'middle' key", isset($attrs['notes']['middle']));
            $this->test("notes has 'base' key", isset($attrs['notes']['base']));
        }

        $this->output->writeln("\n   Sample attributes_json structure:");
        $this->output->writeln('   '.json_encode($attrs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function validateOptionJson(): void
    {
        $this->section('SECTION 10: OPTION_JSON STRUCTURE (Product Variants)');

        $variant = ProductVariant::first();
        if (! $variant) {
            $this->addWarning('No variants to validate option_json');

            return;
        }

        $this->test('Variant has option_json', ! empty($variant->option_json));

        if (empty($variant->option_json)) {
            return;
        }

        $options = $variant->option_json;

        $this->test("option_json has 'size_ml' key", isset($options['size_ml']));
        $this->test("option_json has 'gender' key", isset($options['gender']));
        $this->test("option_json has 'concentration' key", isset($options['concentration']));

        $this->output->writeln("\n   Sample option_json structure:");
        $this->output->writeln('   '.json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function validateRelationshipChain(): void
    {
        $this->section('SECTION 11: RELATIONSHIP CHAIN VALIDATION');

        $product = Product::with(['variants.inventory'])->first();
        if (! $product) {
            $this->addWarning('No products to validate relationship chain');

            return;
        }

        $this->test('Product has variants', $product->variants->count() > 0);

        if ($product->variants->isEmpty()) {
            return;
        }

        $variant = $product->variants->first();
        $this->test('Variant has inventory relationship', ! empty($variant->inventory));

        if (empty($variant->inventory)) {
            return;
        }

        $this->test('Inventory has stock column', isset($variant->inventory->stock));
        $this->test('Inventory stock is integer', is_int($variant->inventory->stock));

        $this->output->writeln("\n   Relationship chain validated:");
        $this->output->writeln("   Product ({$product->name})");
        $this->output->writeln("     └─> Variant (SKU: {$variant->sku})");
        $this->output->writeln("          └─> Inventory (Stock: {$variant->inventory->stock})");
    }

    protected function validateRedis(): void
    {
        $this->section('SECTION 12: REDIS');

        try {
            $redis = app('redis');
            $ping = $redis->connection()->ping();
            $this->test('Redis connection works', $ping === true || $ping === 1 || $ping === '+PONG' || $ping === 'PONG');
        } catch (\Exception $e) {
            $this->test('Redis connection works', false, $e->getMessage());
        }
    }

    protected function printSummary(): void
    {
        $this->output->writeln('');
        $this->output->writeln('╔══════════════════════════════════════════════════════════════════════╗');
        $this->output->writeln('║                           VALIDATION SUMMARY                         ║');
        $this->output->writeln('╠══════════════════════════════════════════════════════════════════════╣');
        $this->output->writeln(sprintf('║  ✅ Passed: %-3d                                                      ║', $this->passed));
        $this->output->writeln(sprintf('║  ❌ Failed: %-3d                                                      ║', $this->failed));
        $this->output->writeln(sprintf('║  ⚠️  Warnings: %-3d                                                    ║', $this->warnings));
        $this->output->writeln('╠══════════════════════════════════════════════════════════════════════╣');

        if ($this->failed === 0) {
            $this->output->writeln('║  🎉 ALL CRITICAL TESTS PASSED - Ready for development!              ║');
        } else {
            $this->output->writeln('║  ⚠️  SOME TESTS FAILED - Review errors above                        ║');
        }

        if (Product::count() === 0) {
            $this->output->writeln("║  📌 ACTION REQUIRED: Run 'php artisan db:seed' to populate data     ║");
        }

        $this->output->writeln('╚══════════════════════════════════════════════════════════════════════╝');
        $this->output->writeln('');
    }
}
