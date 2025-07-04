<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['user', 'repairedBy'])->latest()->get();
        return response()->json([
            'status' => 'success',
            'data' => $tickets
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'contact_number' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'device_category' => 'required|in:iphone,android,other',
            'device_model' => 'required|string',
            'imei' => 'nullable|string',
            'issue' => 'required|string',
            'service_charge' => 'nullable|numeric|min:0'
        ]);

        $ticket = Ticket::create([
            'user_id' => Auth::user()->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'contact_number' => $request->contact_number,
            'priority' => strtolower($request->priority),
            'device_category' => strtolower($request->device_category),
            'device_model' => $request->device_model,
            'imei' => $request->imei,
            'issue' => $request->issue,
            'service_charge' => $request->service_charge ?? 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'data' => $ticket->load('user')
        ], 201);
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user', 'repairedBy'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|required|in:open,in_progress,completed',
            'service_charge' => 'sometimes|required|numeric|min:0',
            'repaired_by' => 'sometimes|nullable|exists:users,id'
        ]);

        $updateData = [];
        
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        
        if ($request->has('service_charge')) {
            $updateData['service_charge'] = $request->service_charge;
        }

        if ($request->has('repaired_by')) {
            $updateData['repaired_by'] = $request->repaired_by;
        }

        $ticket->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'data' => $ticket->fresh()->load(['user', 'repairedBy'])
        ]);
    }

    public function filter(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');

        $query = Ticket::query()
            ->with(['user:id,name', 'repairedBy:id,name']) // Select only needed fields
            ->select([
                'id',
                'user_id',
                'repaired_by',
                'first_name',
                'last_name',
                'contact_number',
                'device_model',
                'device_category',
                'priority',
                'status',
                'service_charge',
                'created_at'
            ])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $tickets = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total()
            ]
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 20);

        $query = Ticket::query()
            ->with(['user:id,name', 'repairedBy:id,name'])
            ->select([
                'id',
                'user_id',
                'repaired_by',
                'first_name',
                'last_name',
                'contact_number',
                'device_model',
                'device_category',
                'priority',
                'status',
                'service_charge',
                'created_at'
            ]);

        // Apply search filter
        if ($search) {
            $query->where(function($query) use ($search) {
                $query->where('device_model', 'LIKE', "%{$search}%")
                      ->orWhere('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('id', $search);
            });
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total()
            ]
        ]);
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]);
    }
}
