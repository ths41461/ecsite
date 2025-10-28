<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Get the reviews for the user.
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class);
    }
    
    /**
     * Check if user has purchased a product.
     */
    public function hasPurchasedProduct($productId)
    {
        return $this->orders()
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'paid')
            ->exists();
    }
    
    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class);
    }
    
    /**
     * Get the addresses for the user.
     */
    public function addresses()
    {
        return $this->hasMany(\App\Models\UserAddress::class);
    }
    
    /**
     * Get the default address for the user.
     */
    public function defaultAddress()
    {
        return $this->hasOne(\App\Models\UserAddress::class)->where('is_default', true);
    }
    
    /**
     * Get the wishlist items for the user.
     */
    public function wishlist()
    {
        return $this->hasMany(\App\Models\Wishlist::class);
    }
}
