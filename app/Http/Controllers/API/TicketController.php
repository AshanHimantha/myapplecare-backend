<?php

namespace App\Http\Controllers\API;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class TicketController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/tickets",
     *     tags={"Tickets"},
     *     summary="Get all tickets",
     *     description="Retrieve a list of all tickets",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Ticket"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $tickets = Ticket::with(['user', 'repairedBy'])->latest()->get();
        return response()->json([
            'status' => 'success',
            'data' => $tickets
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tickets",
     *     tags={"Tickets"},
     *     summary="Create a new ticket",
     *     description="Create a new support ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","contact_number","priority","device_category","device_model","issue"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="contact_number", type="string", example="+1234567890"),
     *             @OA\Property(property="priority", type="string", enum={"low","medium","high"}, example="medium"),
     *             @OA\Property(property="device_category", type="string", enum={"iphone","android","other"}, example="iphone"),
     *             @OA\Property(property="device_model", type="string", example="iPhone 14 Pro"),
     *             @OA\Property(property="imei", type="string", example="123456789012345"),
     *             @OA\Property(property="issue", type="string", example="Screen not working"),
     *             @OA\Property(property="service_charge", type="number", format="float", example=150.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Ticket created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Ticket")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/tickets/{id}",
     *     tags={"Tickets"},
     *     summary="Get ticket by ID",
     *     description="Retrieve a specific ticket by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ticket ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Ticket")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Ticket not found")
     * )
     */
    public function show($id)
    {
        $ticket = Ticket::with(['user', 'repairedBy'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/tickets/{id}",
     *     tags={"Tickets"},
     *     summary="Update a ticket",
     *     description="Update a specific ticket's details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ticket ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"open","in_progress","completed"}, example="in_progress"),
     *             @OA\Property(property="service_charge", type="number", format="float", example=200.00),
     *             @OA\Property(property="repaired_by", type="integer", example=2, description="User ID who will repair the ticket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Ticket updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Ticket")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Ticket not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|required|in:open,in_progress,completed',
            'service_charge' => 'sometimes|required|numeric|min:0',
            'repaired_by' => 'sometimes|nullable|exists:users,id',
            'payment_type' => 'sometimes|nullable|string',
            'imei' => 'sometimes|nullable|string',
        ]);

        $updateData = [];
        $previousStatus = $ticket->status;
        
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        
        if ($request->has('service_charge')) {
            $updateData['service_charge'] = $request->service_charge;
        }

        if ($request->has('repaired_by')) {
            $updateData['repaired_by'] = $request->repaired_by;
        }
        if ($request->has('payment_type')) {
            $updateData['payment_type'] = $request->payment_type;
        }
        if ($request->has('imei')) {
            $updateData['imei'] = $request->imei;
        }

        $ticket->update($updateData);

        // Send SMS if ticket status changed to completed
        if ($request->has('status') && $request->status === 'completed' && $previousStatus !== 'completed') {
            $this->sendTicketCompletionSMS($ticket->contact_number, $ticket->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'data' => $ticket->fresh()->load(['user', 'repairedBy'])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/filter",
     *     tags={"Tickets"},
     *     summary="Filter tickets",
     *     description="Filter tickets by status with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by ticket status",
     *         @OA\Schema(type="string", enum={"open","in_progress","completed"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of tickets per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Ticket")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/tickets/search",
     *     tags={"Tickets"},
     *     summary="Search tickets",
     *     description="Search tickets by device model, customer name, contact number, or ticket ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by ticket status",
     *         @OA\Schema(type="string", enum={"open","in_progress","completed"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of tickets per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Ticket")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
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
                      ->orWhere('contact_number', 'LIKE', "%{$search}%")
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

    /**
     * @OA\Delete(
     *     path="/api/tickets/{id}",
     *     tags={"Tickets"},
     *     summary="Delete a ticket",
     *     description="Delete a specific ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ticket ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Ticket deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Ticket not found")
     * )
     */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]);
    }

    /**
     * Send SMS notification when ticket is completed
     */
    private function sendTicketCompletionSMS($contactNumber, $ticketId)
    {
        try {
            $apiKey = config('services.sms.api_key');
            $apiUrl = config('services.sms.api_url');
            $sourceAddress = config('services.sms.source_address');
            
            // Clean phone number (remove any non-numeric characters except +)
            $phoneNumber = preg_replace('/[^0-9+]/', '', $contactNumber);
            
            // Ensure Sri Lankan format (add 94 if starts with 0)
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '94' . substr($phoneNumber, 1);
            }
            

            $randomNumbers = rand(100, 999);
            $message = "Dear Customer,\n\nGreat news! Your repair ticket has been completed.\nTicket ID: #{$ticketId}\n\nView your ticket details:\nhttps://myapplecare.1000dtechnology.com/ticket/{$ticketId}{$randomNumbers}\n\nYour device is ready for pickup.\n\nThank you for choosing {$sourceAddress}!\n\nBest regards,\n{$sourceAddress} Team";

            $response = Http::get($apiUrl, [
                'esmsqk' => $apiKey,
                'list' => $phoneNumber,
                'source_address' => $sourceAddress,
                'message' => $message
            ]);
            
            // Log SMS response for debugging
            Log::info('Ticket Completion SMS API Response', [
                'phone' => $phoneNumber,
                'ticket_id' => $ticketId,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            // Log error but don't fail the ticket update process
            Log::error('Ticket completion SMS sending failed', [
                'phone' => $contactNumber,
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
