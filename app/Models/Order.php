<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Order.php
class Order extends Model {
  use HasFactory;
  protected $casts = ['ordered_at'=>'datetime','shipped_at'=>'datetime','delivered_at'=>'datetime','canceled_at'=>'datetime'];
  protected $fillable = ['order_number','user_id','email','name','phone','address_line1','address_line2','city','state','zip','subtotal_yen','tax_yen','shipping_yen','discount_yen','total_yen','payment_mode','status','ordered_at','shipped_at','delivered_at','canceled_at'];
  public function items(){ return $this->hasMany(OrderItem::class); }
  public function payments(){ return $this->hasMany(Payment::class); }
  public function shipments(){ return $this->hasMany(Shipment::class); }
  public function user(){ return $this->belongsTo(User::class); }
}

