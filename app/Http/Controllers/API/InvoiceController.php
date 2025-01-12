<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

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
}
