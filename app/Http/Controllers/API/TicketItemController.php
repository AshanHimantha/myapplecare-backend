<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TicketItem;
use App\Models\Part;
use App\Models\Repair;
use Illuminate\Http\Request;

class TicketItemController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'type' => 'required|in:part,repair',
            'part_id' => 'required_if:type,part|exists:parts,id',
            'repair_id' => 'required_if:type,repair|exists:repairs,id',
            'quantity' => 'required_if:type,part|integer|min:1',
            'serial' => 'nullable|string'
        ]);

        // Check for existing items
        if ($request->type === 'part') {
            $exists = TicketItem::where('ticket_id', $request->ticket_id)
                ->where('part_id', $request->part_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This part is already added to the ticket'
                ], 422);
            }
        } else {
            $exists = TicketItem::where('ticket_id', $request->ticket_id)
                ->where('repair_id', $request->repair_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This repair is already added to the ticket'
                ], 422);
            }
        }

        $ticketItem = TicketItem::create([
            'ticket_id' => $request->ticket_id,
            'type' => $request->type,
            'part_id' => $request->part_id,
            'repair_id' => $request->repair_id,
            'quantity' => $request->quantity,
            'serial' => $request->serial
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket item added successfully',
            'data' => $ticketItem->load(['part', 'repair'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ticketItem = TicketItem::findOrFail($id);

        $request->validate([
            'quantity' => 'required_if:type,part|integer|min:1'
        ]);

        $ticketItem->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket item updated successfully',
            'data' => $ticketItem->load(['part', 'repair'])
        ]);
    }

    public function destroy($id)
    {
        $ticketItem = TicketItem::findOrFail($id);
        $ticketItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket item removed successfully'
        ]);
    }

    public function getTicketItems($ticketId)
    {
        $items = TicketItem::with(['part', 'repair'])
            ->where('ticket_id', $ticketId)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }
}
