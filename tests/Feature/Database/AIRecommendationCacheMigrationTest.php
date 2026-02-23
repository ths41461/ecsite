<?php

/**
 * @group feature
 * @group database
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('ai_recommendation_cache table has correct columns', function () {
    $this->assertTrue(Schema::hasTable('ai_recommendation_cache'));

    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'id'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'cache_key'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'context_hash'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'product_ids_json'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'explanation'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'expires_at'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'created_at'));
    $this->assertTrue(Schema::hasColumn('ai_recommendation_cache', 'updated_at'));

    $dbName = DB::connection()->getDatabaseName();
    $uniqueConstraints = DB::select("
        SELECT COLUMN_NAME 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'ai_recommendation_cache' 
        AND NON_UNIQUE = 0
        AND INDEX_NAME != 'PRIMARY'
    ", [$dbName]);
    $uniqueColumns = collect($uniqueConstraints)->pluck('COLUMN_NAME')->toArray();
    $this->assertContains('cache_key', $uniqueColumns, 'cache_key should have unique constraint');
});

test('ai_recommendation_cache model can create record', function () {
    $uniqueKey = 'ai_rec_'.uniqid().'_'.md5(json_encode(['budget' => 5000, 'personality' => 'romantic']));
    $cache = \App\Models\AIRecommendationCache::create([
        'cache_key' => $uniqueKey,
        'context_hash' => md5(json_encode(['budget' => 5000, 'personality' => 'romantic'])),
        'product_ids_json' => [1, 2, 3, 4, 5],
        'explanation' => 'Based on your romantic personality, we recommend floral fragrances.',
        'expires_at' => now()->addHours(24),
    ]);

    $this->assertDatabaseHas('ai_recommendation_cache', [
        'cache_key' => $cache->cache_key,
    ]);

    expect($cache->cache_key)->toStartWith('ai_rec_');
    expect($cache->product_ids_json)->toBe([1, 2, 3, 4, 5]);
    expect($cache->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('ai_recommendation_cache model has correct fillable attributes', function () {
    $model = new \App\Models\AIRecommendationCache;
    $fillable = $model->getFillable();

    expect($fillable)->toContain('cache_key');
    expect($fillable)->toContain('context_hash');
    expect($fillable)->toContain('product_ids_json');
    expect($fillable)->toContain('explanation');
    expect($fillable)->toContain('expires_at');
});

test('ai_recommendation_cache model casts product_ids_json as array', function () {
    $cache = \App\Models\AIRecommendationCache::create([
        'cache_key' => 'test_cache_'.uniqid(),
        'context_hash' => md5('test'),
        'product_ids_json' => [10, 20, 30, 40, 50, 60, 70],
        'explanation' => 'Test explanation',
        'expires_at' => now()->addHour(),
    ]);

    $retrieved = \App\Models\AIRecommendationCache::find($cache->id);

    expect($retrieved->product_ids_json)->toBeArray();
    expect($retrieved->product_ids_json)->toBe([10, 20, 30, 40, 50, 60, 70]);
});

test('ai_recommendation_cache model casts expires_at as datetime', function () {
    $futureTime = now()->addHours(48);

    $cache = \App\Models\AIRecommendationCache::create([
        'cache_key' => 'test_datetime_'.uniqid(),
        'context_hash' => md5('datetime'),
        'product_ids_json' => [1],
        'explanation' => null,
        'expires_at' => $futureTime,
    ]);

    $retrieved = \App\Models\AIRecommendationCache::find($cache->id);

    expect($retrieved->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($retrieved->expires_at->timestamp)->toBe($futureTime->timestamp);
});

test('ai_recommendation_cache cache_key must be unique', function () {
    $cacheKey = 'duplicate_key_'.uniqid();

    \App\Models\AIRecommendationCache::create([
        'cache_key' => $cacheKey,
        'context_hash' => md5('first'),
        'product_ids_json' => [1],
        'expires_at' => now()->addHour(),
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    \App\Models\AIRecommendationCache::create([
        'cache_key' => $cacheKey,
        'context_hash' => md5('second'),
        'product_ids_json' => [2],
        'expires_at' => now()->addHour(),
    ]);
});

test('ai_recommendation_cache can check if expired', function () {
    $expiredCache = \App\Models\AIRecommendationCache::create([
        'cache_key' => 'expired_'.uniqid(),
        'context_hash' => md5('expired'),
        'product_ids_json' => [1],
        'expires_at' => now()->subHour(),
    ]);

    $validCache = \App\Models\AIRecommendationCache::create([
        'cache_key' => 'valid_'.uniqid(),
        'context_hash' => md5('valid'),
        'product_ids_json' => [2],
        'expires_at' => now()->addHour(),
    ]);

    expect($expiredCache->expires_at->isPast())->toBeTrue();
    expect($validCache->expires_at->isFuture())->toBeTrue();
});
