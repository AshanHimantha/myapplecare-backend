<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with('product')->get();
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
