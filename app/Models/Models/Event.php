<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Event.php
class Event extends Model {
  use HasFactory;
  protected $fillable = ['product_id','user_id','user_hash','event_type','value','occurred_at','meta_json'];
  protected $casts = ['occurred_at'=>'datetime','meta_json'=>'array'];
  public function product(){ return $this->belongsTo(Product::class); }
  public function user(){ return $this->belongsTo(User::class); }
}

