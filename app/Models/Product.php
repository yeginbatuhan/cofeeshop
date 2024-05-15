<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'flavor_notes' => 'array',
    ];

    public function cartDetails()
    {
        return $this->hasMany(CartDetail::class);
    }

    public function categories()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
