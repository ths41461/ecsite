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
}
