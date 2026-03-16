<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'answers_json',
        'profile_type',
        'profile_data_json',
        'recommended_product_ids',
    ];

    protected $casts = [
        'answers_json' => 'array',
        'profile_data_json' => 'array',
        'recommended_product_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
