<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartItemController extends Controller
{
    // ADD ITEM TO CART
    public function store(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,cart_id',
            'product_id' => 'required|exists:products,product_id',
            'size_id' => 'required|exists:sizes,id',
            'quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {

            $cart = Cart::findOrFail($request->cart_id);

            // 🔥 Prevent add to closed cart
            if ($cart->status !== 'open') {
                return response()->json([
                    'message' => 'Cart is already checked out'
                ], 400);
            }

            $product = Product::findOrFail($request->product_id);

            $productSize = $product->sizes()
                ->where('sizes.id', $request->size_id)
                ->first();

            if (!$productSize) {
                return response()->json([
                    'message' => 'Invalid product size'
                ], 422);
            }

            $price = $productSize->pivot->price;

            $item = CartItem::where('cart_id', $request->cart_id)
                ->where('product_id', $request->product_id)
                ->where('size_id', $request->size_id)
                ->first();

            if ($item) {
                $item->quantity += $request->quantity;
            } else {
                $item = new CartItem([
                    'cart_id' => $request->cart_id,
                    'product_id' => $request->product_id,
                    'size_id' => $request->size_id,
                    'price' => $price,
                    'quantity' => $request->quantity
                ]);
            }

            $item->subtotal = $item->quantity * $price;
            $item->save();

            // Reload cart with relations
            $cart->load(['items.product', 'items.size']);

            return response()->json([
                'message' => 'Item added successfully',
                'cart' => $cart
            ], 201);
        });
    }

    // UPDATE CART ITEM
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::findOrFail($id);

        $item->quantity = $request->quantity;
        $item->subtotal = $request->quantity * $item->price;
        $item->save();

        return response()->json([
            'message' => 'Item updated',
            'data' => $item->load(['product', 'size'])
        ]);
    }

    // DELETE CART ITEM
    public function destroy($id)
    {
        $item = CartItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Item removed'
        ]);
    }
}