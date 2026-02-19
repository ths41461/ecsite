<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIRecommendationCache extends Model
{
    use HasFactory;

    protected $table = 'ai_recommendation_cache';

    protected $fillable = [
        'cache_key',
        'context_hash',
        'product_ids_json',
        'explanation',
        'expires_at',
    ];

    protected $casts = [
        'product_ids_json' => 'array',
        'expires_at' => 'datetime',
    ];
}
