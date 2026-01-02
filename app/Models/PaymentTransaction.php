<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'provider',
        'ext_id',
        'amount_yen',
        'currency',
        'status',
        'payload_json',
        'occurred_at',
    ];

    protected $casts = [
        'amount_yen' => 'integer',
        'occurred_at' => 'datetime',
        'payload_json' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}