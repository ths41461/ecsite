<?php

/**
 * Test script to verify radar data calculation
 * Run with: php tests/test-radar-calculation.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FragranceRadarService;

// Test data from actual seeders
$testProducts = [
    [
        'name' => 'SHIRO - ホワイトリリー オードパルファン',
        'notes' => [
            'top' => 'ベルガモット、グリーン',
            'middle' => 'ホワイトリリー、ジャスミン',
            'base' => 'ムスク、シダーウッド',
        ],
    ],
    [
        'name' => 'SHIRO - サボン オードパルファン',
        'notes' => [
            'top' => 'レモン、オレンジ',
            'middle' => 'ローズ、ジャスミン',
            'base' => 'ムスク、アンバー',
        ],
    ],
    [
        'name' => 'COSME DECORTE - キモノ ユイ オードトワレ',
        'notes' => [
            'top' => '日本のカンキツ、ピンクペッパー',
            'middle' => 'オレンジフラワー',
            'base' => 'バニラ',
        ],
    ],
    [
        'name' => 'Issey Miyake - ロードゥ イッセイ',
        'notes' => [
            'top' => 'シトラス、フローラル',
            'middle' => 'アクアティックノート、ホワイトフラワー',
            'base' => 'ムスク、シダー',
        ],
    ],
    [
        'name' => '資生堂 - SHISEIDO ZEN',
        'notes' => [
            'top' => 'フルーツ、シトラス',
            'middle' => 'フローラル、アンバー',
            'base' => 'ウッド、パチュリ',
        ],
    ],
    [
        'name' => 'ケンゾー - フラワー バイ ケンゾー EDP',
        'notes' => [
            'top' => 'マンダリン、ブラックカラント',
            'middle' => 'ポピー、ローズ、ジャスミン',
            'base' => 'バニラ、ムスク、インセンス',
        ],
    ],
    [
        'name' => 'ディオール - ジャドール',
        'notes' => [
            'top' => 'ペア、メロン、ベルガモット',
            'middle' => 'ジャスミン、ローズ、リリー',
            'base' => 'バニラ、ムスク、シダー',
        ],
    ],
    [
        'name' => 'トムフォード - ロストチェリー',
        'notes' => [
            'top' => 'チェリー、アーモンド、シナモン',
            'middle' => 'ジャスミン、ローズ',
            'base' => 'バニラ、トンカ、サンダルウッド',
        ],
    ],
    [
        'name' => 'ジョーマローン - ライムバジル＆マンダリン',
        'notes' => [
            'top' => 'ライム、マンダリン',
            'middle' => 'バジル、タイム',
            'base' => 'リリー、ベチバー',
        ],
    ],
    [
        'name' => 'クロエ - クロエ オードパルファム',
        'notes' => [
            'top' => 'ピオニー、フリージア',
            'middle' => 'ローズ、リリーオブザバレー',
            'base' => 'シダー、ムスク、アンバー',
        ],
    ],
];

$service = new FragranceRadarService();

echo "=== Fragrance Radar Calculation Test ===\n\n";

foreach ($testProducts as $product) {
    echo "Product: {$product['name']}\n";
    echo str_repeat('-', 60) . "\n";
    
    $radarData = $service->calculateRadarData($product['notes']);
    
    foreach ($radarData as $dimension => $score) {
        $bar = str_repeat('■', (int)($score / 10)) . str_repeat('□', 10 - (int)($score / 10));
        echo sprintf("  %-12s [%s] %3d\n", $dimension, $bar, $score);
    }
    
    echo "\n\n";
}

echo "=== Test Complete ===\n";
