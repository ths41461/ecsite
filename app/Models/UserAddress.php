<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
        'country',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
