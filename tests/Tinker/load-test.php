<?php

echo "=== Load Testing Benchmark ===\n\n";

$baseUrl = 'http://localhost';

echo "Testing API endpoints...\n\n";

echo "1. Testing Product API (GET /api/products)...\n";
$start = microtime(true);
$response = file_get_contents($baseUrl.'/api/products');
$duration = microtime(true) - $start;
echo '   Response time: '.round($duration * 1000)."ms\n";
echo '   Status: '.($response !== false ? 'OK' : 'FAILED')."\n\n";

echo "2. Testing Product Detail API (GET /api/products/1)...\n";
$start = microtime(true);
$response = file_get_contents($baseUrl.'/api/products/1');
$duration = microtime(true) - $start;
echo '   Response time: '.round($duration * 1000)."ms\n";
echo '   Status: '.($response !== false ? 'OK' : 'FAILED')."\n\n";

echo "3. Testing Database Query Performance...\n";
$start = microtime(true);
$products = App\Models\Product::with(['brand', 'category', 'variants'])
    ->where('is_active', true)
    ->limit(50)
    ->get();
$duration = microtime(true) - $start;
echo '   Query time: '.round($duration * 1000)."ms\n";
echo '   Products fetched: '.$products->count()."\n\n";

echo "4. Testing AI ContextBuilder...\n";
$start = microtime(true);
$builder = new App\Services\AI\ContextBuilder;
$context = $builder->build([
    'budget' => 10000,
    'gender' => 'women',
]);
$duration = microtime(true) - $start;
echo '   Build time: '.round($duration * 1000)."ms\n\n";

echo "5. Testing ToolRegistry (search products)...\n";
$start = microtime(true);
$registry = new App\Services\AI\ToolRegistry;
$result = $registry->execute('search_products', [
    'category' => 'floral',
    'max_price' => 10000,
]);
$duration = microtime(true) - $start;
echo '   Search time: '.round($duration * 1000)."ms\n";
echo '   Results: '.count($result)." products\n\n";

echo "=== Benchmark Complete ===\n";
