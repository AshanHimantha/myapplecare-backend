<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Stock;
use Illuminate\Http\Request;
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
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);

        if ($stock->quantity < $validated['quantity']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cart = Cart::firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'active'
            ]
        );

        $cartItem = CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'stock_id' => $validated['stock_id']
            ],
            [
                'quantity' => $validated['quantity'],
                'price' => $stock->selling_price
            ]
        );

        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'data' => $cart->load('items.stock.product')
        ]);
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        if ($item->stock->quantity < $validated['quantity']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock'
            ], 400);
        }

        $item->update($validated);
        $this->updateCartTotal($item->cart);

        return response()->json([
            'status' => 'success',
            'data' => $item->cart->load('items.stock.product')
        ]);
    }

    public function removeItem(CartItem $item)
    {
        $cart = $item->cart;
        $item->delete();
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'data' => $cart->load('items.stock.product')
        ]);
    }

    public function checkout()
    {
        $user = request()->user();

        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active cart found'
            ], 404);
        }

        // Update stock quantities
        foreach ($cart->items as $item) {
            $stock = $item->stock;
            $stock->quantity -= $item->quantity;
            $stock->save();
        }

        $cart->update(['status' => 'completed']);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart checkout successful',
            'data' => $cart->load('items.stock.product')
        ]);
    }

    private function updateCartTotal(Cart $cart)
    {
        $total = $cart->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $cart->update(['total_amount' => $total]);
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
