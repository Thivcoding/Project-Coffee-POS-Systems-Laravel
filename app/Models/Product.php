<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'category_id',
        'product_code',
        'product_name',
        'image',
        'image_id', 
        'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    
    // 1 Product → Many ProductSizes
    public function productSizes()
    {
        return $this->hasMany(ProductSize::class, 'product_id', 'product_id');
    }

    // Optional: Direct access to Size through pivot
    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_sizes', 'product_id', 'size_id')
                    ->withPivot('price', 'stock_qty')
                    ->withTimestamps();
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'product_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'product_id');
    }
}
