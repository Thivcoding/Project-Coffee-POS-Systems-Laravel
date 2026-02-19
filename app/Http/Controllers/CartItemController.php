<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

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

        // Get product size info
        $product = Product::findOrFail($request->product_id);
        $productSize = $product->sizes()->where('size_id', $request->size_id)->first();

        if (!$productSize) {
            return response()->json([
                'message' => 'Invalid product size'
            ], 422);
        }

        $price = $productSize->pivot->price;

        // Check if item already exists in cart
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

        return response()->json($item, 201);
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

        return response()->json($item);
    }

    // DELETE CART ITEM
    public function destroy($id)
    {
        CartItem::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Item removed'
        ]);
    }
}
