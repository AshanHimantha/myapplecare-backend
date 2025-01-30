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

}
