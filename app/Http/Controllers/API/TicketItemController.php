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
            'quantity' => 'required_if:type,part|integer|min:1'
        ]);

        $data = $request->all();

        if ($request->type === 'part') {
            $part = Part::findOrFail($request->part_id);
            $data['quantity'] = $request->quantity;
        } else {
            $repair = Repair::findOrFail($request->repair_id);
        }

        $ticketItem = TicketItem::create($data);

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
