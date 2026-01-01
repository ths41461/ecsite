<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['product_variant_id', 'stock', 'safety_stock', 'managed'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Accessor for qty (alias for stock)
     */
    public function getQtyAttribute(): int
    {
        return $this->stock;
    }

    /**
     * Get the stock status based on current stock and safety stock levels
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock <= $this->safety_stock) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Update the stock level
     */
    public function updateStock(int $newStock): void
    {
        $this->update(['stock' => max(0, $newStock)]);
    }

    /**
     * Increment the stock level
     */
    public function incrementStock(int $amount = 1): void
    {
        $this->update(['stock' => $this->stock + $amount]);
    }

    /**
     * Decrement the stock level
     */
    public function decrementStock(int $amount = 1): void
    {
        $newStock = max(0, $this->stock - $amount);
        $this->update(['stock' => $newStock]);
    }

    /**
     * Check if the inventory is low (stock is at or below safety stock level)
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->safety_stock;
    }

    /**
     * Check if the inventory is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Check if the inventory is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Scope to get inventories with low stock
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'safety_stock')
                     ->where('stock', '>', 0);
    }

    /**
     * Scope to get out of stock inventories
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    /**
     * Scope to get in stock inventories
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Get the stock level as a percentage of safety stock
     * Returns null if safety stock is 0 to avoid division by zero
     */
    public function getStockLevelPercentageAttribute(): ?float
    {
        if ($this->safety_stock <= 0) {
            return null;
        }
        return round(($this->stock / $this->safety_stock) * 100, 2);
    }
}
