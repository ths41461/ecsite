<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** simple helper to check if an index already exists */
    private function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, $table, $index]
        );
        return (bool) $row;
    }

    public function up(): void
    {
        // products: (category_id, is_active), (created_at)
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id') && Schema::hasColumn('products', 'is_active')) {
            if (!$this->indexExists('products', 'products_category_active_idx')) {
                Schema::table('products', fn(Blueprint $t) => $t->index(['category_id', 'is_active'], 'products_category_active_idx'));
            }
        }
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'created_at') && !$this->indexExists('products', 'products_created_at_idx')) {
            Schema::table('products', fn(Blueprint $t) => $t->index('created_at', 'products_created_at_idx'));
        }

        // product_variants: (product_id) — PDP load & variant lookup
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'product_id') && !$this->indexExists('product_variants', 'product_variants_product_idx')) {
            Schema::table('product_variants', fn(Blueprint $t) => $t->index('product_id', 'product_variants_product_idx'));
        }

        // orders: (user_id, created_at) and unique(number) if column exists
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id') && Schema::hasColumn('orders', 'created_at') && !$this->indexExists('orders', 'orders_user_created_idx')) {
            Schema::table('orders', fn(Blueprint $t) => $t->index(['user_id', 'created_at'], 'orders_user_created_idx'));
        }
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'number') && !$this->indexExists('orders', 'orders_number_unique')) {
            Schema::table('orders', fn(Blueprint $t) => $t->unique('number', 'orders_number_unique'));
        }

        // order_items: (order_id)
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'order_id') && !$this->indexExists('order_items', 'order_items_order_idx')) {
            Schema::table('order_items', fn(Blueprint $t) => $t->index('order_id', 'order_items_order_idx'));
        }

        // payments: (order_id)
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'order_id') && !$this->indexExists('payments', 'payments_order_idx')) {
            Schema::table('payments', fn(Blueprint $t) => $t->index('order_id', 'payments_order_idx'));
        }

        // shipments: (order_id)
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'order_id') && !$this->indexExists('shipments', 'shipments_order_idx')) {
            Schema::table('shipments', fn(Blueprint $t) => $t->index('order_id', 'shipments_order_idx'));
        }

        // product_metrics_daily: (product_id, date)
        if (Schema::hasTable('product_metrics_daily') && Schema::hasColumn('product_metrics_daily', 'product_id') && Schema::hasColumn('product_metrics_daily', 'date') && !$this->indexExists('product_metrics_daily', 'pmd_product_date_idx')) {
            Schema::table('product_metrics_daily', fn(Blueprint $t) => $t->index(['product_id', 'date'], 'pmd_product_date_idx'));
        }

        // events: (product_id, created_at)
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'product_id') && Schema::hasColumn('events', 'created_at') && !$this->indexExists('events', 'events_product_created_idx')) {
            Schema::table('events', fn(Blueprint $t) => $t->index(['product_id', 'created_at'], 'events_product_created_idx'));
        }
    }

    public function down(): void
    {
        // Drop safely; ignore if missing
        $drops = [
            ['products', 'products_category_active_idx'],
            ['products', 'products_created_at_idx'],
            ['product_variants', 'product_variants_product_idx'],
            ['orders', 'orders_user_created_idx'],
            ['orders', 'orders_number_unique'], // unique index
            ['order_items', 'order_items_order_idx'],
            ['payments', 'payments_order_idx'],
            ['shipments', 'shipments_order_idx'],
            ['product_metrics_daily', 'pmd_product_date_idx'],
            ['events', 'events_product_created_idx'],
        ];
        foreach ($drops as [$table, $index]) {
            if (Schema::hasTable($table) && $this->indexExists($table, $index)) {
                try {
                    Schema::table($table, function (Blueprint $t) use ($index) {
                        // Laravel handles both dropIndex/dropUnique by name with dropIndex
                        $t->dropIndex($index);
                    });
                } catch (\Throwable $e) { /* noop */
                }
            }
        }
    }
};
