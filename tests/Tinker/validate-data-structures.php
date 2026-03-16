<?php

/**
 * AI Fragrance Recommendation Agent - Data Structure Validation Script
 *
 * This script validates that the actual database structure matches
 * what is documented in AI_AGENT_ARCHITECTURE.md and AI_AGENT_PRD.md
 *
 * Run: ./vendor/bin/sail php artisan tinker < tests/Tinker/validate-data-structures.php
 *
 * @version 1.0
 *
 * @date 2026-02-18
 */
echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║     AI FRAGRANCE RECOMMENDATION AGENT - DATA VALIDATION              ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

function test($name, $condition, $message = '')
{
    global $passed, $failed, $warnings;

    if ($condition) {
        echo "✅ PASS: {$name}\n";
        $passed++;

        return true;
    } else {
        echo "❌ FAIL: {$name}";
        if ($message) {
            echo " - {$message}";
        }
        echo "\n";
        $failed++;

        return false;
    }
}

function warn($message)
{
    global $warnings;
    echo "⚠️  WARN: {$message}\n";
    $warnings++;
}

// ============================================================================
// SECTION 1: DATABASE CONNECTION & BASIC CHECKS
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 1: DATABASE CONNECTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$dbName = DB::connection()->getDatabaseName();
test('Database connection', $dbName === 'laravel', "Expected 'laravel', got '{$dbName}'");

$tableCount = count(DB::select('SHOW TABLES'));
test('Tables exist', $tableCount > 0, 'No tables found');
echo "   → Found {$tableCount} tables\n";

// ============================================================================
// SECTION 2: PRODUCTS TABLE VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 2: PRODUCTS TABLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $columns = collect(DB::select('DESCRIBE products'))->pluck('Type', 'Field');

    test('products.name column exists', isset($columns['name']));
    test('products.name is varchar(120)', $columns['name'] === 'varchar(120)', "Got: {$columns['name']}");

    test('products.attributes_json exists', isset($columns['attributes_json']));
    test('products.attributes_json is json', str_contains($columns['attributes_json'], 'json'), "Got: {$columns['attributes_json']}");

    test('products.meta_json exists', isset($columns['meta_json']));
    test('products.brand_id exists', isset($columns['brand_id']));
    test('products.category_id exists', isset($columns['category_id']));

    // Check that columns documented as NOT EXISTING don't exist
    test('products.top_notes does NOT exist', ! isset($columns['top_notes']), 'This column should NOT exist!');
    test('products.middle_notes does NOT exist', ! isset($columns['middle_notes']), 'This column should NOT exist!');
    test('products.base_notes does NOT exist', ! isset($columns['base_notes']), 'This column should NOT exist!');

    echo "\n   Full columns list:\n";
    foreach ($columns as $col => $type) {
        echo "   - {$col}: {$type}\n";
    }

} catch (Exception $e) {
    test('Products table query', false, $e->getMessage());
}

// ============================================================================
// SECTION 3: INVENTORIES TABLE VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 3: INVENTORIES TABLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $columns = collect(DB::select('DESCRIBE inventories'))->pluck('Type', 'Field');

    test('inventories.id exists', isset($columns['id']));
    test('inventories.product_variant_id exists', isset($columns['product_variant_id']));
    test('inventories.stock exists', isset($columns['stock']));
    test('inventories.safety_stock exists', isset($columns['safety_stock']));
    test('inventories.managed exists', isset($columns['managed']));

    // CRITICAL: product_id should NOT exist
    test('inventories.product_id does NOT exist', ! isset($columns['product_id']),
        'CRITICAL: This column should NOT exist! Use product_variant_id instead');

    // quantity should NOT exist (it's called stock)
    test('inventories.quantity does NOT exist', ! isset($columns['quantity']),
        "Column is called 'stock', not 'quantity'");

    echo "\n   Full columns list:\n";
    foreach ($columns as $col => $type) {
        echo "   - {$col}: {$type}\n";
    }

} catch (Exception $e) {
    test('Inventories table query', false, $e->getMessage());
}

// ============================================================================
// SECTION 4: REVIEWS TABLE VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 4: REVIEWS TABLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $columns = collect(DB::select('DESCRIBE reviews'))->pluck('Type', 'Field');

    test('reviews.product_id exists', isset($columns['product_id']));
    test('reviews.rating exists', isset($columns['rating']));
    test('reviews.body exists', isset($columns['body']));

    // CRITICAL: column is 'approved', NOT 'is_approved'
    test('reviews.approved exists', isset($columns['approved']),
        "Column is 'approved', NOT 'is_approved'");

    test('reviews.is_approved does NOT exist', ! isset($columns['is_approved']),
        "CRITICAL: Use 'approved', not 'is_approved'");

    echo "\n   Full columns list:\n";
    foreach ($columns as $col => $type) {
        echo "   - {$col}: {$type}\n";
    }

} catch (Exception $e) {
    test('Reviews table query', false, $e->getMessage());
}

// ============================================================================
// SECTION 5: PRODUCT_VARIANTS TABLE VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 5: PRODUCT_VARIANTS TABLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $columns = collect(DB::select('DESCRIBE product_variants'))->pluck('Type', 'Field');

    test('product_variants.product_id exists', isset($columns['product_id']));
    test('product_variants.sku exists', isset($columns['sku']));
    test('product_variants.option_json exists', isset($columns['option_json']));
    test('product_variants.price_yen exists', isset($columns['price_yen']));

    echo "\n   Full columns list:\n";
    foreach ($columns as $col => $type) {
        echo "   - {$col}: {$type}\n";
    }

} catch (Exception $e) {
    test('Product_variants table query', false, $e->getMessage());
}

// ============================================================================
// SECTION 6: AI TABLES VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 6: AI TABLES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ai_chat_sessions
try {
    $columns = collect(DB::select('DESCRIBE ai_chat_sessions'))->pluck('Type', 'Field');
    test('ai_chat_sessions table exists', true);
    test('ai_chat_sessions.session_token exists', isset($columns['session_token']));
    test('ai_chat_sessions.context_json exists', isset($columns['context_json']));
} catch (Exception $e) {
    test('ai_chat_sessions table exists', false, $e->getMessage());
}

// ai_messages
try {
    $columns = collect(DB::select('DESCRIBE ai_messages'))->pluck('Type', 'Field');
    test('ai_messages table exists', true);
    test('ai_messages.session_id exists', isset($columns['session_id']));
    test('ai_messages.role exists', isset($columns['role']));
    test('ai_messages.content exists', isset($columns['content']));
    test('ai_messages.metadata_json exists', isset($columns['metadata_json']));
} catch (Exception $e) {
    test('ai_messages table exists', false, $e->getMessage());
}

// quiz_results
try {
    $columns = collect(DB::select('DESCRIBE quiz_results'))->pluck('Type', 'Field');
    test('quiz_results table exists', true);
    test('quiz_results.session_token exists', isset($columns['session_token']));
    test('quiz_results.answers_json exists', isset($columns['answers_json']));
    test('quiz_results.profile_type exists', isset($columns['profile_type']));
} catch (Exception $e) {
    test('quiz_results table exists', false, $e->getMessage());
}

// user_scent_profiles
try {
    $columns = collect(DB::select('DESCRIBE user_scent_profiles'))->pluck('Type', 'Field');
    test('user_scent_profiles table exists', true);
    test('user_scent_profiles.user_id exists', isset($columns['user_id']));
    test('user_scent_profiles.profile_type exists', isset($columns['profile_type']));
} catch (Exception $e) {
    test('user_scent_profiles table exists', false, $e->getMessage());
}

// ai_recommendation_cache
try {
    $columns = collect(DB::select('DESCRIBE ai_recommendation_cache'))->pluck('Type', 'Field');
    test('ai_recommendation_cache table exists', true);
    test('ai_recommendation_cache.cache_key exists', isset($columns['cache_key']));
    test('ai_recommendation_cache.expires_at exists', isset($columns['expires_at']));
} catch (Exception $e) {
    test('ai_recommendation_cache table exists', false, $e->getMessage());
}

// ============================================================================
// SECTION 7: MODEL RELATIONSHIPS VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 7: MODEL RELATIONSHIPS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Product model
$productModel = file_get_contents(app_path('Models/Product.php'));
test('Product has brand() relationship', str_contains($productModel, 'function brand()'));
test('Product has category() relationship', str_contains($productModel, 'function category()'));
test('Product has variants() relationship', str_contains($productModel, 'function variants()'));
test('Product has reviews() relationship', str_contains($productModel, 'function reviews()'));

// Product should NOT have inventory() directly (it's through variants)
if (str_contains($productModel, 'function inventory()')) {
    warn('Product has inventory() relationship - this goes through variants!');
}

// Inventory model
$inventoryModel = file_get_contents(app_path('Models/Inventory.php'));
test('Inventory belongs to variant', str_contains($inventoryModel, 'function variant()'));

// ProductVariant model
$variantModel = file_get_contents(app_path('Models/ProductVariant.php'));
test('ProductVariant belongs to product', str_contains($variantModel, 'function product()'));
test('ProductVariant has one inventory', str_contains($variantModel, 'function inventory()'));

// ============================================================================
// SECTION 8: DATA CONTENT VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 8: DATA CONTENT (Requires Seeding)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$productCount = App\Models\Product::count();
$brandCount = App\Models\Brand::count();
$categoryCount = App\Models\Category::count();
$variantCount = App\Models\ProductVariant::count();
$inventoryCount = App\Models\Inventory::count();

test('Products table has data', $productCount > 0, "Run 'php artisan db:seed'");
test('Brands table has data', $brandCount > 0, "Run 'php artisan db:seed'");
test('Categories table has data', $categoryCount > 0, "Run 'php artisan db:seed'");

if ($productCount > 0) {
    echo "\n   Data counts:\n";
    echo "   - Products: {$productCount}\n";
    echo "   - Brands: {$brandCount}\n";
    echo "   - Categories: {$categoryCount}\n";
    echo "   - Variants: {$variantCount}\n";
    echo "   - Inventories: {$inventoryCount}\n";
} else {
    warn('Database is empty! Run: php artisan db:seed');
}

// ============================================================================
// SECTION 9: ATTRIBUTES_JSON STRUCTURE VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 9: ATTRIBUTES_JSON STRUCTURE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($productCount > 0) {
    $product = App\Models\Product::first();

    test('Product has attributes_json', ! empty($product->attributes_json));

    if (! empty($product->attributes_json)) {
        $attrs = $product->attributes_json;

        test('attributes_json is array', is_array($attrs));
        test("attributes_json has 'notes' key", isset($attrs['notes']));
        test("attributes_json has 'gender' key", isset($attrs['gender']));

        if (isset($attrs['notes'])) {
            test("notes has 'top' key", isset($attrs['notes']['top']));
            test("notes has 'middle' key", isset($attrs['notes']['middle']));
            test("notes has 'base' key", isset($attrs['notes']['base']));
        }

        echo "\n   Sample attributes_json structure:\n";
        echo "   {\n";
        foreach ($attrs as $key => $value) {
            if (is_array($value)) {
                echo "     \"{$key}\": {\n";
                foreach ($value as $k => $v) {
                    $vStr = is_string($v) ? "\"{$v}\"" : json_encode($v);
                    echo "       \"{$k}\": {$vStr},\n";
                }
                echo "     },\n";
            } else {
                $vStr = is_string($value) ? "\"{$value}\"" : json_encode($value);
                echo "     \"{$key}\": {$vStr},\n";
            }
        }
        echo "   }\n";
    }
}

// ============================================================================
// SECTION 10: OPTION_JSON STRUCTURE VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 10: OPTION_JSON STRUCTURE (Product Variants)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($variantCount > 0) {
    $variant = App\Models\ProductVariant::first();

    test('Variant has option_json', ! empty($variant->option_json));

    if (! empty($variant->option_json)) {
        $options = $variant->option_json;

        test("option_json has 'size_ml' key", isset($options['size_ml']));
        test("option_json has 'gender' key", isset($options['gender']));
        test("option_json has 'concentration' key", isset($options['concentration']));

        echo "\n   Sample option_json structure:\n";
        echo '   '.json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
    }
}

// ============================================================================
// SECTION 11: RELATIONSHIP CHAIN VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 11: RELATIONSHIP CHAIN VALIDATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($productCount > 0 && $variantCount > 0 && $inventoryCount > 0) {
    $product = App\Models\Product::with(['variants.inventory'])->first();

    test('Product has variants', $product->variants->count() > 0);

    $variant = $product->variants->first();
    test('Variant has inventory relationship', ! empty($variant->inventory));

    if (! empty($variant->inventory)) {
        test('Inventory has stock column', isset($variant->inventory->stock));
        test('Inventory stock is integer', is_int($variant->inventory->stock));

        echo "\n   Relationship chain validated:\n";
        echo "   Product ({$product->name})\n";
        echo "     └─> Variant (SKU: {$variant->sku})\n";
        echo "          └─> Inventory (Stock: {$variant->inventory->stock})\n";
    }
}

// ============================================================================
// SECTION 12: REDIS VALIDATION
// ============================================================================

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECTION 12: REDIS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $redis = app('redis');
    $ping = $redis->connection()->ping();
    test('Redis connection works', $ping === 1 || $ping === '+PONG' || $ping === 'PONG');
} catch (Exception $e) {
    test('Redis connection works', false, $e->getMessage());
}

// ============================================================================
// FINAL SUMMARY
// ============================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║                           VALIDATION SUMMARY                         ║\n";
echo "╠══════════════════════════════════════════════════════════════════════╣\n";
printf("║  ✅ Passed: %-3d                                                      ║\n", $passed);
printf("║  ❌ Failed: %-3d                                                      ║\n", $failed);
printf("║  ⚠️  Warnings: %-3d                                                    ║\n", $warnings);
echo "╠══════════════════════════════════════════════════════════════════════╣\n";

if ($failed === 0) {
    echo "║  🎉 ALL CRITICAL TESTS PASSED - Ready for development!              ║\n";
} else {
    echo "║  ⚠️  SOME TESTS FAILED - Review errors above                        ║\n";
}

if ($productCount === 0) {
    echo "║  📌 ACTION REQUIRED: Run 'php artisan db:seed' to populate data     ║\n";
}

echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

// Return exit code
exit($failed > 0 ? 1 : 0);
