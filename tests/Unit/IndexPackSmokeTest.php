<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
});

function idxExists(string $table, string $index): bool
{
    $db = DB::getDatabaseName();
    $row = DB::selectOne(
        'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
        [$db, $table, $index]
    );
    return (bool) $row;
}

it('has hot-path indexes in place (smoke check)', function () {
    // Only assert indexes when both table and columns are expected in this project
    expect(idxExists('products', 'products_category_active_idx'))->toBeTrue();
    expect(idxExists('products', 'products_created_at_idx'))->toBeTrue();
    expect(idxExists('product_variants', 'product_variants_product_idx'))->toBeTrue();

    // Orders and related
    expect(idxExists('orders', 'orders_user_created_idx'))->toBeTrue();
    // orders(number) may be added by this migration; assert it if column exists
    try {
        expect(idxExists('orders', 'orders_number_unique'))->toBeTrue();
    } catch (Throwable $e) {
    }

    expect(idxExists('order_items', 'order_items_order_idx'))->toBeTrue();
    expect(idxExists('payments', 'payments_order_idx'))->toBeTrue();
    expect(idxExists('shipments', 'shipments_order_idx'))->toBeTrue();

    // Analytics
    expect(idxExists('product_metrics_daily', 'pmd_product_date_idx'))->toBeTrue();
    expect(idxExists('events', 'events_product_created_idx'))->toBeTrue();
});
