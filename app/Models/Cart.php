<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function cartDetails()
    {
        return $this->hasMany(CartDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function getTotalPriceAttribute()
    {
        return $this->cartDetails->sum(function ($cartDetail) {
            return $cartDetail->count * $cartDetail->price;
        });
    }
}
