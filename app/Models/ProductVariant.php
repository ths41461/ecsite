<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'option_json', 'price_yen', 'sale_price_yen',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'option_json' => 'array',
    ];

    public function product()   { return $this->belongsTo(Product::class); }
    public function inventory() { return $this->hasOne(Inventory::class); }

    /** Happy-path finder for PDP/cart flows */
    public static function findBySku(string $sku): self
    {
        return static::where('sku', $sku)->firstOrFail();
    }

    /**
     * Get the current stock level for this variant
     */
    public function getCurrentStock(): int
    {
        return $this->inventory?->stock ?? 0;
    }

    /**
     * Check if this variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->inventory?->isInStock() ?? false;
    }

    /**
     * Check if this variant is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->inventory?->isOutOfStock() ?? true;
    }

    /**
     * Check if this variant has low stock
     */
    public function isLowStock(): bool
    {
        return $this->inventory?->isLowStock() ?? false;
    }

    /**
     * Get the stock status for this variant
     */
    public function getStockStatus(): string
    {
        return $this->inventory?->stock_status ?? 'out_of_stock';
    }
}
