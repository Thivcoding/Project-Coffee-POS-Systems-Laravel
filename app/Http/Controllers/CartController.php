<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // GET /carts
    public function index()
    {
        return Cart::where('user_id', Auth::id())
            ->with('items.product', 'items.size')
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
        $cart = Cart::with('items.product', 'items.size')
            ->where('cart_id', $id)
            ->where('user_id', Auth::id()) // 🔥 protect ownership
            ->firstOrFail();

        return response()->json($cart);
    }

    // POST /carts/{cart}/checkout
    public function checkout(Cart $cart)
    {
        // 🔥 Ownership protection
        if ($cart->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // 🔥 Prevent checkout empty cart
        if ($cart->items()->count() === 0) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 422);
        }

        DB::transaction(function () use ($cart) {

            // Update cart status
            $cart->update([
                'status' => 'checked_out'
            ]);

            // 🔥 Later you can:
            // - Create Sale
            // - Reduce stock
            // - Save payment
        });

        return response()->json([
            'message' => 'Checkout successful',
            'cart_id' => $cart->cart_id
        ]);
    }
}