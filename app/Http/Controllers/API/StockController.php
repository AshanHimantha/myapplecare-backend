<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with('product');
        
        // Get limit with default of 50 records
        $limit = $request->has('limit') && is_numeric($request->limit) ? (int)$request->limit : 50;
        
        // Apply the limit
        $query->limit($limit);
        
        $stocks = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $stocks
        ]);
    }


    public function available()
{
    $stocks = Stock::with('product')
        ->where('quantity', '>', 0)
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $stocks
    ]);
}

    public function search(Request $request)
    {
        $query = Stock::query()->with('product');

        // Search by product name
        if ($request->has('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        // Filter by condition
        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        // Filter by serial number
        if ($request->has('serial_number')) {
            $query->where('serial_number', 'like', '%' . $request->serial_number . '%');
        }

        // Filter by color
        if ($request->has('color')) {
            $query->where('color', 'like', '%' . $request->color . '%');
        }

        // Filter by quantity range
        if ($request->has('min_quantity')) {
            $query->where('quantity', '>=', $request->min_quantity);
        }

        if ($request->has('max_quantity')) {
            $query->where('quantity', '<=', $request->max_quantity);
        }

        // Filter by price range (selling price)
        if ($request->has('min_price')) {
            $query->where('selling_price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('selling_price', '<=', $request->max_price);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $stocks = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $stocks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'condition' => 'required|in:new,used',
            'serial_number' => 'nullable|string|unique:stocks',
            'color' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0'
        ]);

        $stock = Stock::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $stock->load('product')
        ], 201);
    }

    public function show(Stock $stock)
    {
        return response()->json([
            'status' => 'success',
            'data' => $stock->load('product')
        ]);
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'condition' => 'sometimes|in:new,used',
            'serial_number' => 'nullable|string|unique:stocks,serial_number,' . $stock->id,
            'quantity' => 'sometimes|integer|min:0',
            'color' => 'nullable|string',
            'selling_price' => 'sometimes|numeric|min:0',
            'cost_price' => 'sometimes|numeric|min:0'
        ]);

        $stock->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $stock->fresh('product')
        ]);
    }

    public function destroy(Stock $stock)
    {
        $stock->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock deleted successfully'
        ]);
    }
}
