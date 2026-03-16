<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('pestphp is working correctly', function () {
    expect(true)->toBeTrue();
    expect(1 + 1)->toBe(2);
});

test('database connection is working', function () {
    $dbName = DB::connection()->getDatabaseName();
    expect($dbName)->toBe('laravel');

    $result = DB::select('SELECT 1 as test');
    expect($result[0]->test)->toBe(1);
});

test('products table exists', function () {
    $tableExists = Schema::hasTable('products');
    expect($tableExists)->toBeTrue();
});

test('products table has correct columns', function () {
    expect(Schema::hasColumn('products', 'id'))->toBeTrue();
    expect(Schema::hasColumn('products', 'name'))->toBeTrue();
    expect(Schema::hasColumn('products', 'slug'))->toBeTrue();
    expect(Schema::hasColumn('products', 'brand_id'))->toBeTrue();
    expect(Schema::hasColumn('products', 'category_id'))->toBeTrue();
    expect(Schema::hasColumn('products', 'is_active'))->toBeTrue();
});

test('test environment is properly configured', function () {
    expect(env('APP_ENV'))->toBe('testing');
    expect(config('database.default'))->toBe('mysql');
});
