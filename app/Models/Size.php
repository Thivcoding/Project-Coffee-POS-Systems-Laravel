<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'sort_order'
    ];

    // 1 Size → Many ProductSizes
    public function productSizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    // Many-to-Many with Product
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_sizes', 'size_id', 'product_id')
                    ->withPivot('price', 'stock_qty')
                    ->withTimestamps();
    }
}
