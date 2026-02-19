<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\ProductSize;
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
            'details.size', // include size
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
            'details.size',
            'payment',
            'user'
        ])->where('sale_id', $id)->firstOrFail();

        return response()->json($data);
    }

    /**
     * POST /sales
     * Create sale from cart
     */
    public function store($cartId)
    {
        $cart = Cart::with(['items.product', 'items.size'])
            ->where('cart_id', $cartId)
            ->where('status', 'open')
            ->firstOrFail();

            
        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $total = $cart->items->sum('subtotal');

            $sale = Sale::create([
                'cart_id'      => $cart->cart_id,
                'user_id'      => $cart->user_id,
                'total_amount' => $total,
                'status'       => 'pending',
                'sale_date'    => now(),
            ]);

            foreach ($cart->items as $item) {

                // 🔥 Lock row to prevent overselling
                $productSize = ProductSize::where([
                        'product_id' => $item->product_id,
                        'size_id'    => $item->size_id
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($productSize->stock_qty < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Not enough stock for {$item->product->product_name} (Size: {$item->size->name})"
                    ], 400);
                }

                SaleDetail::create([
                    'sale_id'    => $sale->sale_id,
                    'product_id' => $item->product_id,
                    'size_id'    => $item->size_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'subtotal'   => $item->subtotal,
                ]);

                $productSize->decrement('stock_qty', $item->quantity);
            }

            $cart->update(['status' => 'checked_out']);

            DB::commit();

            return response()->json(
                $sale->load(['details.product', 'details.size', 'user']),
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

        if ($sale->status === 'paid') {
            return response()->json([
                'message' => 'Cannot delete a paid sale'
            ], 403);
        }

        DB::transaction(function () use ($sale) {
            // restore stock per product size
            foreach ($sale->details as $detail) {
                $productSize = ProductSize::where('product_id', $detail->product_id)
                    ->where('size_id', $detail->size_id)
                    ->first();

                if ($productSize) {
                    $productSize->increment('stock_qty', $detail->quantity);
                }
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
