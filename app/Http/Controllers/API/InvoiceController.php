<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ReturnedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with(['items', 'user'])
            ->when($request->date_from, function($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->date_to);
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $invoices
        ]);
    }

      public function show($id)
    {
        $invoice = Invoice::with(['items.product', 'user'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $invoice
        ]);
    }
    public function daily()
    {
        $invoices = Invoice::with(['items'])
            ->whereDate('created_at', today())
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $invoices
        ]);
    }

        public function search(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);

        $query = Invoice::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'total_amount',
                'created_at'
            ]);

        if ($search) {
            $query->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total()
            ]
        ]);
    }

        public function processReturn(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.return_type' => 'required|in:stock,damaged',
        ]);

        try {
            DB::beginTransaction();

            // Find the invoice
            $invoice = Invoice::with(['items.stock'])->findOrFail($request->invoice_id);

            foreach ($request->items as $returnItem) {
                // Find the invoice item
                $invoiceItem = $invoice->items()->where('id', $returnItem['item_id'])->first();
                
                if (!$invoiceItem) {
                    throw new \Exception("Item not found in invoice");
                }

                // Validate return quantity
                if ($returnItem['quantity'] > $invoiceItem->quantity) {
                    throw new \Exception("Return quantity cannot exceed purchased quantity");
                }

                // Process based on return type
                if ($returnItem['return_type'] === 'stock') {
                    // Add back to stock
                    $stock = $invoiceItem->stock;
                    if ($stock) {
                        $stock->increment('quantity', $returnItem['quantity']);
                    } else {
                        throw new \Exception("Stock record not found");
                    }
                }

                // Calculate new quantity
                $newQuantity = $invoiceItem->quantity - $returnItem['quantity'];

                if ($newQuantity === 0) {
                    // If quantity becomes 0, delete the invoice item
                    $invoiceItem->delete();
                } else {
                    // Update invoice item quantity
                    $invoiceItem->update([
                        'quantity' => $newQuantity
                    ]);
                }

                // Create return record
                ReturnedItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $invoiceItem->product_id,
                    'stock_id' => $invoiceItem->stock_id,
                    'quantity' => $returnItem['quantity'],
                    'return_type' => $returnItem['return_type'],
                    'returned_at' => now(),
                ]);
            }

            // Refresh invoice relationship and recalculate total
            $invoice->refresh();
            $newTotal = $invoice->items->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });
            $invoice->update(['total_amount' => $newTotal]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Return processed successfully',
                'data' => $invoice->fresh(['items.stock'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function returnedItems(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        $returnedItems = ReturnedItem::with(['invoice', 'product', 'stock'])
            ->when($request->date_from, function($query) use ($request) {
                return $query->whereDate('returned_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function($query) use ($request) {
                return $query->whereDate('returned_at', '<=', $request->date_to);
            })
            ->orderBy('returned_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $returnedItems->items(),
            'meta' => [
                'current_page' => $returnedItems->currentPage(),
                'last_page' => $returnedItems->lastPage(),
                'per_page' => $returnedItems->perPage(),
                'total' => $returnedItems->total()
            ]
        ]);
    }
}
