<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $database = DB::getDatabaseName();
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$database, $table, $constraint]
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    public function up(): void
    {
        // --- categories: add light tree fields if missing ---
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->index('parent_id');
            }
            if (!Schema::hasColumn('categories', 'depth')) {
                $table->unsignedTinyInteger('depth')->default(0)->after('parent_id');
                $table->index('depth');
            }
        });

        // --- products: ensure category_id exists + FK to categories(id) with RESTRICT ---
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('brand_id');
                $table->index('category_id');
            }
        });

        // Clean up any rows with invalid category_id before adding FK
        DB::statement("
            UPDATE products p
            LEFT JOIN categories c ON c.id = p.category_id
            SET p.category_id = NULL
            WHERE p.category_id IS NOT NULL AND c.id IS NULL
        ");

        Schema::table('products', function (Blueprint $table) {
            // Drop any prior FK if it exists (name-agnostic best-effort)
            try {
                $table->dropForeign(['category_id']);
            } catch (\Throwable $e) {
            }
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        // --- category_product pivot (unique category_id+product_id) ---
        if (!Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('product_id');
                $table->unique(['category_id', 'product_id'], 'category_product_unique');
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('category_product')) {
            Schema::table('category_product', function (Blueprint $table) {
                // handled outside using explicit drops
            });
            $this->dropForeignIfExists('category_product', 'category_product_category_id_foreign');
            $this->dropForeignIfExists('category_product', 'category_product_product_id_foreign');
            Schema::dropIfExists('category_product');
        }

        Schema::table('products', function (Blueprint $table) {
            // keep the column; removing could break app queries. If you want to drop:
            // $table->dropColumn('category_id');
        });
        $this->dropForeignIfExists('products', 'products_category_id_foreign');

        $this->dropForeignIfExists('categories', 'categories_parent_id_foreign');
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'depth')) {
                $table->dropIndex(['depth']);
                $table->dropColumn('depth');
            }
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropIndex(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
