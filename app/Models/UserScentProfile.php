<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_type',
        'profile_data_json',
        'preferences_json',
    ];

    protected $casts = [
        'profile_data_json' => 'array',
        'preferences_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
