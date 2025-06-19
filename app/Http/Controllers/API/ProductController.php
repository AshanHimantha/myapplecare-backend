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
            $validated['image'] = 'products/' . $filename;
        }

        $product = Product::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $this->appendImageUrl($product->load(['deviceCategory', 'deviceSubcategory']))
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'device_category_id' => 'sometimes|exists:device_categories,id',
            'device_subcategory_id' => 'sometimes|exists:device_subcategories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
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
            $validated['image'] = 'products/' . $filename;
        }

        $product->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $this->appendImageUrl($product->fresh(['deviceCategory', 'deviceSubcategory']))
        ]);
    }

    public function index()
    {
        $products = Product::with(['deviceCategory', 'deviceSubcategory'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $this->appendImageUrl($products)
        ]);
    }

    public function search($id)
    {
        $product = Product::with(['deviceCategory', 'deviceSubcategory'])->find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->appendImageUrl($product)
        ]);
    }

    /**
     * Append full URL to image paths
     */
    private function appendImageUrl($data)
    {
        if ($data instanceof \Illuminate\Database\Eloquent\Collection) {
            return $data->map(function ($item) {
                if ($item->image) {
                    $item->image_url = url('storage/' . $item->image);
                }
                return $item;
            });
        } else {
            if ($data->image) {
                $data->image_url = url('storage/' . $data->image);
            }
            return $data;
        }
    }
}
