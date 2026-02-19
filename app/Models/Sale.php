<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'cart_id',
        'user_id',
        'total_amount',
        'status', 
        'sale_date'
    ];

    protected $casts = [
        'sale_date' => 'datetime'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'sale_id');
    }
}
