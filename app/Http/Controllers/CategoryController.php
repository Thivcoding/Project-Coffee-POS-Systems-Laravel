<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /api/categories
    public function index()
    {
        return response()->json(
            Category::orderBy('category_id','desc')->get()
        );
    }

    // POST /api/categories
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description'   => 'nullable|string'
        ]);

        $category = Category::create($request->only([
            'category_name','description'
        ]));

        return response()->json([
            'message' => 'Category created successfully',
            'data'    => $category
        ], 201);
    }

    // GET /api/categories/{id}
    public function show($id)
    {
        $category = Category::findOrFail($id);

        return response()->json($category);
    }

    // PUT /api/categories/{id}
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'category_name' => 'required|string|max:255',
            'description'   => 'nullable|string'
        ]);

        $category->update($request->only([
            'category_name','description'
        ]));

        return response()->json([
            'message' => 'Category updated successfully',
            'data'    => $category
        ]);
    }

    // DELETE /api/categories/{id}
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
