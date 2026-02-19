<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // GET /carts
    public function index()
    {
        return Cart::where('user_id', Auth::id())
            ->with('items.product')
            ->get();
    }

    // POST /carts
    public function store()
    {
        $cart = Cart::firstOrCreate([
            'user_id' => Auth::id(),
            'status' => 'open'
        ]);

        return response()->json($cart, 201);
    }

    // GET /carts/{id}
    public function show($id)
    {
        return Cart::with('items.product')
            ->where('cart_id', $id)
            ->firstOrFail();
    }

    // POST /carts/{cart}/checkout
    public function checkout(Cart $cart)
    {
        $cart->update(['status' => 'checked_out']);

        return response()->json([
            'message' => 'Checkout successful',
            'cart_id' => $cart->cart_id
        ]);
    }
}

