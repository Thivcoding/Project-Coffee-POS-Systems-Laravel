<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;
     protected $fillable = [
        'product_id',
        'size_id',
        'price',
        'stock_qty'
    ];

    // Belongs to Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // Belongs to Size
    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id', 'id');
    }
}
