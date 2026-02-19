<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * GET /sales
     */
    public function index()
    {
        $data = Sale::with([
            'details.product',
            'payment',
            'user'
        ])->orderByDesc('sale_id')->get();

        return response()->json($data);
    }

    /**
     * GET /sales/{id}
     */
    public function show($id)
    {
        $data = Sale::with([
            'details.product',
            'payment',
            'user'
        ])->where('sale_id', $id)->firstOrFail();

        return response()->json($data);
    }

    /**
     * POST /sales
     * Create sale from cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,cart_id'
        ]);

        $cart = Cart::with('items.product')
            ->where('cart_id', $request->cart_id)
            ->where('status', 'open')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // calculate total
            $total = $cart->items->sum(function ($item) {
                return $item->subtotal;
            });

            // create sale (INVOICE)
            $sale = Sale::create([
                'cart_id'      => $cart->cart_id,
                'user_id'      => $cart->user_id,
                'total_amount' => $total,
                'status'       => 'pending',
                'sale_date'    => now(),
            ]);

            // create sale details
            foreach ($cart->items as $item) {

                SaleDetail::create([
                    'sale_id'    => $sale->sale_id,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'subtotal'   => $item->subtotal,
                ]);

                // update product stock
                $item->product->decrement('stock_qty', $item->quantity);
            }

            // close cart
            $cart->update([
                'status' => 'checked_out'
            ]);

            DB::commit();

            return response()->json(
                $sale->load('details.product'),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create sale',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /sales/{id}
     */
    public function destroy($id)
    {
        $sale = Sale::with('details', 'payment')->findOrFail($id);

        // ❌ do not allow delete paid sale
        if ($sale->status === 'paid') {
            return response()->json([
                'message' => 'Cannot delete a paid sale'
            ], 403);
        }

        DB::transaction(function () use ($sale) {

            // restore stock (optional but recommended)
            foreach ($sale->details as $detail) {
                $detail->product->increment('stock_qty', $detail->quantity);
            }

            $sale->details()->delete();
            $sale->payment()->delete();
            $sale->delete();
        });

        return response()->json([
            'message' => 'Sale deleted successfully'
        ]);
    }
}
