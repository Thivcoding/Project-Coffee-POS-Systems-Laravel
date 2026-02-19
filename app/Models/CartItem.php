<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $primaryKey = 'cart_item_id';
    
    protected $fillable = [
        'cart_id',
        'product_id',
        'size_id',
        'quantity',
        'price',
        'subtotal'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    // 🔥 THIS IS REQUIRED because you use items.productSize
    public function productSize()
    {
        return $this->belongsTo(ProductSize::class, 
            ['product_id', 'size_id'], 
            ['product_id', 'size_id']
        );
    }
}
