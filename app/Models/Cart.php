<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $primaryKey = 'cart_id';

    protected $fillable = [
        'user_id',
        'status'
    ];

    protected $appends = ['total'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id', 'cart_id');
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'cart_id', 'cart_id');
    }

    public function getTotalAttribute()
    {
        if ($this->relationLoaded('items')) {
            return $this->items->sum('subtotal');
        }

        return $this->items()->sum('subtotal');
    }
}