<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMetricsDaily extends Model
{
    use HasFactory;

    protected $table = 'product_metrics_daily';
    public $timestamps = false;
    public $incrementing = false; // composite key managed via upsert

    protected $fillable = [
        'product_id', 'date', 'views', 'atc', 'orders', 'revenue_yen',
        'search_impr', 'search_clicks', 'wishlist_adds',
        'rating_avg', 'rating_count',
    ];

    protected $casts = [
        'date'        => 'date',
        'rating_avg'  => 'float',
        'views'       => 'integer',
        'atc'         => 'integer',
        'orders'      => 'integer',
        'revenue_yen' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** convenience helper for bulk upsert */
    public static function upsertDaily(array $rows): void
    {
        static::query()->upsert(
            $rows,
            ['product_id', 'date'], // uniqueBy
            ['views','atc','orders','revenue_yen','search_impr','search_clicks','wishlist_adds','rating_avg','rating_count']
        );
    }
}
