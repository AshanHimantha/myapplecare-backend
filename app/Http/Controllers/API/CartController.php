<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class CartController extends Controller
{
       public function index()
    {
        $user = request()->user();

        $carts = Cart::where('user_id', $user->id)
            ->with(['items.stock.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $carts,
            'total_carts' => $carts->count()
        ]);
    }

    public function addItem(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'cart_id' => 'sometimes|exists:carts,id',
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'sometimes|numeric|min:0'
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);

        if ($stock->quantity < $validated['quantity']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock'
            ], 400);
        }



        // Get or create cart based on cart_id if provided
        $cart = isset($validated['cart_id'])
            ? Cart::where('id', $validated['cart_id'])
                ->where('user_id', $user->id)
                ->firstOrFail()
            : Cart::firstOrCreate([
                'user_id' => $user->id,
                'status' => 'active'
            ]);

        $price = $validated['price'] ?? $stock->selling_price;

        $cartItem = CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'stock_id' => $validated['stock_id']
            ],
            [
                'quantity' => $validated['quantity'],
                'price' => $price
            ]
        );

        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'data' => [
                'cart_item' => $cartItem,
                'cart' => $cart->fresh()
            ]
        ]);
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0'  // price field will store discount amount
        ]);

        $stock = $item->stock;
        $maxAllowedDiscount = $stock->selling_price - $stock->cost_price;

        if (isset($validated['quantity']) && $stock->quantity < $validated['quantity']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock'
            ], 400);
        }

        // Validate entered discount amount
        if (isset($validated['price'])) {
            $enteredDiscount = $validated['price'];
            if ($enteredDiscount > $maxAllowedDiscount) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Maximum discount allowed is {$maxAllowedDiscount}"
                ], 400);
            }
            // Store discount amount in price field
            $validated['price'] = $enteredDiscount;
        }

        $item->update($validated);
        $cart = $item->cart()->first();
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'data' => $cart->load('items.stock.product')
        ]);
    }

    public function removeItem(CartItem $item)
    {
        $cart = $item->cart()->first();
        $item->delete();
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'data' => $cart->load('items.stock.product')
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'contact_number' => 'required|string',
            'payment_method' => 'required|in:cash,card',
            'total_amount' => 'required|numeric|min:0'
        ]);

        // Load cart with relationships
        $cart = Cart::with('items.stock.product')
            ->where('id', $request->cart_id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active cart found'
            ], 404);
        }

        // Store cart data before deletion
        $cartData = $cart->toArray();

        // Create Invoice
        $invoice = Invoice::create([
            'user_id' => $cart->user_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'contact_number' => $request->contact_number,
            'payment_method' => $request->payment_method,
            'total_amount' => $request->total_amount
        ]);

        // Create Invoice Items
        foreach ($cart->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item->stock->product_id,
                'stock_id' => $item->stock_id,
                'sold_price' => $item->stock->selling_price,
                'cost_price' => $item->stock->cost_price,
                'discount' => $item->price,
                'quantity' => $item->quantity,
                'serial_number'=> $item->stock->serial_number
            ]);

            $stock = $item->stock;
            $stock->quantity -= $item->quantity;
            $stock->save();
        }

        // Mark cart as completed and delete
        $cart->update(['status' => 'completed']);
        $cart->items()->delete();
        $cart->delete();

        // Send SMS notification
        $this->sendSMSNotification($request->contact_number, $invoice->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Checkout successful',
            'data' => [
                'cart' => $cartData,
                'invoice' => $invoice->load('items')
            ]
        ]);
    }


    private function updateCartTotal(Cart $cart)
    {
        $cart->load('items');
        $total = 0;
        foreach ($cart->items as $item) {
            $total += $item->quantity * $item->price;
        }

        $cart->update(['total_amount' => $total]);
    }

    /**
     * Send SMS notification after successful invoice creation
     */
    private function sendSMSNotification($contactNumber, $invoiceId)
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
             $message = "Dear Customer,\n\nThank you for choosing {$sourceAddress}!\n\nYour purchase has been completed successfully.\nInvoice ID: #{$invoiceId}\n\nView your invoice online:\nhttps://myapplecare.1000dtechnology.com/customer-invoice/{$invoiceId}{$randomNumbers}\n\nWe appreciate your trust in our services.\n\nBest regards,\n{$sourceAddress} Team";
            
            $response = Http::get($apiUrl, [
                'esmsqk' => $apiKey,
                'list' => $phoneNumber,
                'source_address' => $sourceAddress,
                'message' => $message
            ]);
            
            // Log SMS response for debugging
            Log::info('SMS API Response', [
                'phone' => $phoneNumber,
                'invoice_id' => $invoiceId,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            // Log error but don't fail the checkout process
            Log::error('SMS sending failed', [
                'phone' => $contactNumber,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function destroy(Cart $cart)
    {
        $user = request()->user();

        if ($cart->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $cart->items()->delete();
        $cart->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart deleted successfully'
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        // Check if user has reached max cart limit (e.g., 5 active carts)
        $activeCartsCount = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        if ($activeCartsCount >= 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maximum number of active carts reached (5)',
                'active_carts' => $activeCartsCount
            ], 400);
        }

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
            'total_amount' => 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart created successfully',
            'data' => $cart->load('items'),
            'active_carts_count' => $activeCartsCount + 1
        ], 201);
    }

        public function show($id)
    {
        $user = request()->user();

        $cart = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['items.stock.product'])
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cart
        ]);
    }
}
