<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // GET /api/admin/products
    public function index()
    {
        // load category + sizes
        $products = Product::with(['category','sizes'])->latest()->get();
        return response()->json($products);
    }

    // POST /api/admin/products
    public function store(Request $request)
    {

        // Decode sizes JSON string if sent as form-data string
        if (is_string($request->sizes)) {
            $request->merge(['sizes' => json_decode($request->sizes, true)]);
        }

        $request->validate([
            'category_id'       => 'required|exists:categories,category_id',
            'product_code'      => 'required|unique:products,product_code',
            'product_name'      => 'required|string|max:255',
            'sizes'             => 'required|array|min:1',
            'sizes.*.size_id'   => 'required|exists:sizes,id',
            'sizes.*.price'     => 'required|numeric',
            'sizes.*.stock_qty' => 'required|integer',
            'image' => 'nullable|image|max:2048'
        ]);

        DB::beginTransaction();

        try {
            // Upload image
            $upload = cloudinary()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'pos-products-laravel']
            );

            // Create Product
            $product = Product::create([
                'category_id'  => $request->category_id,
                'product_code' => $request->product_code,
                'product_name' => $request->product_name,
                'image'        => $upload->getSecurePath(),
                'image_id'     => $upload->getPublicId(),
                'status'       => 'active'
            ]);

            // Insert sizes
            foreach ($request->sizes as $size) {
                ProductSize::create([
                    'product_id' => $product->product_id,
                    'size_id'    => $size['size_id'],
                    'price'      => $size['price'],
                    'stock_qty'  => $size['stock_qty']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created with sizes successfully',
                'data'    => $product->load('sizes')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/admin/products/{id}
    public function show($id)
    {
        $product = Product::with(['category','sizes'])->findOrFail($id);
        return response()->json($product);
    }

    // PUT /api/admin/products/{id}
    public function update(Request $request, $id)
    {

        $product = Product::findOrFail($id);

        // Decode sizes JSON string if sent as form-data string
        if (is_string($request->sizes)) {
            $request->merge(['sizes' => json_decode($request->sizes, true)]);
        }

        $request->validate([
            'category_id'       => 'required|exists:categories,category_id',
            'product_name'      => 'required|string|max:255',
            'sizes'             => 'required|array|min:1',
            'sizes.*.size_id'   => 'required|exists:sizes,id',
            'sizes.*.price'     => 'required|numeric',
            'sizes.*.stock_qty' => 'required|integer',
            'image'             => 'nullable|image|max:2048'
        ]);

        DB::beginTransaction();

        try {
            // Update image
            if ($request->hasFile('image')) {
                if ($product->image_id) {
                    cloudinary()->destroy($product->image_id);
                }

                $upload = cloudinary()->upload(
                    $request->file('image')->getRealPath(),
                    ['folder' => 'pos-products-laravel']
                );

                $product->image    = $upload->getSecurePath();
                $product->image_id = $upload->getPublicId();
            }

            // Update product
            $product->update([
                'category_id'  => $request->category_id,
                'product_name' => $request->product_name,
            ]);

            // Delete old sizes
            ProductSize::where('product_id', $product->product_id)->delete();

            // Insert new sizes
            foreach ($request->sizes as $size) {
                ProductSize::create([
                    'product_id' => $product->product_id,
                    'size_id'    => $size['size_id'],
                    'price'      => $size['price'],
                    'stock_qty'  => $size['stock_qty']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data'    => $product->load('sizes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/admin/products/{id}
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image_id) {
            cloudinary()->destroy($product->image_id);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
