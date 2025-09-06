<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope', 'rank', 'product_id', 'score', 'computed_at',
    ];

    protected $casts = [
        'score'       => 'float',
        'computed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeLatestForScope($q, string $scope)
    {
        return $q->where('scope', $scope)->orderByDesc('computed_at');
    }
}
