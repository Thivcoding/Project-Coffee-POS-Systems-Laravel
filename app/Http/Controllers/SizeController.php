<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Size;

class SizeController extends Controller
{
    // GET /api/admin/sizes
    public function index()
    {
        return response()->json(Size::orderBy('sort_order')->get());
    }

    // POST /api/admin/sizes
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255|unique:sizes,name',
            'sort_order' => 'nullable|integer'
        ]);

        $size = Size::create([
            'name'       => $request->name,
            'sort_order' => $request->sort_order ?? 0
        ]);

        return response()->json([
            'message' => 'Size created successfully',
            'data'    => $size
        ], 201);
    }

    // GET /api/admin/sizes/{id}
    public function show($id)
    {
        $size = Size::findOrFail($id);
        return response()->json($size);
    }

    // PUT /api/admin/sizes/{id}
    public function update(Request $request, $id)
    {
        $size = Size::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255|unique:sizes,name,' . $id,
            'sort_order' => 'nullable|integer'
        ]);

        $size->update([
            'name'       => $request->name,
            'sort_order' => $request->sort_order ?? 0
        ]);

        return response()->json([
            'message' => 'Size updated successfully',
            'data'    => $size
        ]);
    }

    // DELETE /api/admin/sizes/{id}
    public function destroy($id)
    {
        $size = Size::findOrFail($id);
        $size->delete();

        return response()->json([
            'message' => 'Size deleted successfully'
        ]);
    }
}
