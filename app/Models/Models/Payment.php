<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'provider', 'type', 'amount_yen',
        'status', 'payload_json', 'processed_at',
    ];

    protected $casts = [
        'amount_yen'   => 'integer',
        'processed_at' => 'datetime',
        'payload_json' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
