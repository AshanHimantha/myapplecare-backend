<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeviceCategory;
use App\Models\DeviceSubcategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = DeviceCategory::with('deviceSubcategories')->get();
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function show(DeviceCategory $category)
    {
        return response()->json([
            'status' => 'success',
            'data' => $category->load('deviceSubcategories')
        ]);
    }



    public function storeDeviceCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:device_categories',
        ]);

        $category = DeviceCategory::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ], 201);
    }

      public function storeDeviceSubCategory(Request $request)
    {
        $validated = $request->validate([
            'device_category_id' => 'required|exists:device_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:device_subcategories'
        ]);

        $subcategory = DeviceSubcategory::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $subcategory->load('deviceCategory')
        ], 201);
    }





}
