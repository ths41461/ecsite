<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'max_uses',
        'max_uses_per_user',
        'min_subtotal_yen',
        'max_discount_yen',
        'exclude_sale_items',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'value'     => 'integer',
        'max_uses'  => 'integer',
        'used_count'=> 'integer',
        'max_uses_per_user' => 'integer',
        'min_subtotal_yen'  => 'integer',
        'max_discount_yen'  => 'integer',
        'exclude_sale_items'=> 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function isCurrentlyValid(): bool
    {
        $now = now();
        if (!$this->is_active) return false;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    public function redeem(?int $userId, int $orderId): void
    {
        // Basic gates from your coupons schema
        if (!$this->is_active) {
            throw new \DomainException('Coupon is not active.');
        }
        $now = now();
        if (($this->starts_at && $now->lt($this->starts_at)) || ($this->ends_at && $now->gt($this->ends_at))) {
            throw new \DomainException('Coupon is not currently valid.');
        }
        if (!is_null($this->max_uses) && (int)$this->used_count >= (int)$this->max_uses) {
            throw new \DomainException('Coupon usage cap reached.');
        }

        // Attempt single redemption for this order (DB unique handles race conditions)
        DB::transaction(function () use ($userId, $orderId) {
            DB::table('coupon_redemptions')->insert([
                'coupon_id'   => $this->id,
                'order_id'    => $orderId,
                'user_id'     => $userId,
                'redeemed_at' => now(),
            ]);

            // Optional: increment global counter (soft guard, not used for "once per order")
            DB::table('coupons')->where('id', $this->id)->update([
                'used_count' => DB::raw('used_count + 1'),
            ]);
        });
    }
}
