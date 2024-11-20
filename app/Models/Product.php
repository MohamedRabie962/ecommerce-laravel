<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'price', 'image',
        'category_id','is_active', 'brand_id' , 'category_id', 'is_featured','is_stock','on_sale'];

    protected $casts = ['image' => 'array'];

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function brand(){
        return $this->belongsTo(Brand::class);
    }

    public function orderitems(){
        return $this->hasMany(OrderItem::class);
    }

}

