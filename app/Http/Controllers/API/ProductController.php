<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_category_id' => 'required|exists:device_categories,id',
            'device_subcategory_id' => 'required|exists:device_subcategories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('products', $filename, 'public');
            $validated['image'] = $path;
        }

        $product = Product::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $product->load(['deviceCategory', 'deviceSubcategory'])
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'device_category_id' => 'sometimes|exists:device_categories,id',
            'device_subcategory_id' => 'sometimes|exists:device_subcategories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('products', $filename, 'public');
            $validated['image'] = $path;
        }

        $product->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $product->fresh(['deviceCategory', 'deviceSubcategory'])
        ]);
    }
}
