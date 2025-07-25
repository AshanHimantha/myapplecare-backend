<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function index()
    {
        $items = InvoiceItem::with(['invoice', 'product', 'stock'])->latest()->get();
        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'product_id' => 'required|exists:products,id',
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0'
        ]);

        $item = InvoiceItem::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice item created successfully',
            'data' => $item->load(['invoice', 'product', 'stock'])
        ], 201);
    }

    public function show($id)
    {
        $item = InvoiceItem::with(['invoice', 'product', 'stock'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = InvoiceItem::findOrFail($id);

        $request->validate([
            'stock_id' => 'sometimes|required|exists:stocks,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0'
        ]);

        $item->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice item updated successfully',
            'data' => $item->fresh()->load(['invoice', 'product', 'stock'])
        ]);
    }

    public function destroy($id)
    {
        $item = InvoiceItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice item deleted successfully'
        ]);
    }
}
