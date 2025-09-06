<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMetricsCurrent extends Model
{
    use HasFactory;

    protected $table = 'product_metrics_current';
    public $timestamps = false;

    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id', 'units_7d', 'units_30d',
        'conv_rate_pdp', 'atc_rate', 'search_ctr',
        'revenue_30d', 'wishlist_14d',
        'rating_bayes', 'freshness_bonus',
        'stock', 'safety_stock',
    ];

    protected $casts = [
        'units_7d'        => 'integer',
        'units_30d'       => 'integer',
        'conv_rate_pdp'   => 'float',
        'atc_rate'        => 'float',
        'search_ctr'      => 'float',
        'revenue_30d'     => 'integer',
        'wishlist_14d'    => 'integer',
        'rating_bayes'    => 'float',
        'freshness_bonus' => 'float',
        'stock'           => 'integer',
        'safety_stock'    => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}