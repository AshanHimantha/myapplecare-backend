<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\StockController;
use App\Http\Controllers\API\UserController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/me', [AuthController::class, 'me']);
Route::get('invoices', [InvoiceController::class, 'index']);
Route::get('invoices/{id}', [InvoiceController::class, 'show']);
Route::get('invoices/daily', [InvoiceController::class, 'daily']);



// Protected Routes
Route::middleware('auth:sanctum')->group(function () {


    Route::get('stocks/available', [StockController::class, 'available']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/product-images/{filename}', function ($filename) {
        $path = storage_path('app/public/products/' . $filename);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }
        return response()->file($path);
    });

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::put('/cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
    Route::post('/cart/checkout', [CartController::class, 'checkout']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);
    Route::post('/cart/create', [CartController::class, 'create']);
    Route::get('/cart/{id}', [CartController::class, 'show']);

    Route::get('/searchProduct', [ProductController::class, 'index']);
    Route::get('/products/search/{id}', [ProductController::class, 'search']);
    Route::get('/storage/products/{filename}', function ($filename) {
        $path = storage_path('app/public/products/' . $filename);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }
        return response()->file($path);
    });


    // Admin Routes
    Route::group(['middleware' => ['role:admin']], function () {

        Route::controller(UserController::class)->group(function () {
            Route::get('/users', 'index');
            Route::post('/users', 'store');
            Route::get('/users/{user}', 'show');
            Route::put('/users/{user}', 'update');
            Route::delete('/users/{user}', 'destroy');
            Route::post('/create-user', 'store');
        });

        Route::post('/addProduct', [ProductController::class, 'store']);
        Route::put('/updateProduct/{product}', [ProductController::class, 'update']);
        Route::delete('/deleteProduct/{product}', [ProductController::class, 'destroy']);
        Route::post('/device-categories', [CategoryController::class, 'storeDeviceCategory']);
        Route::post('/device-subcategories', [CategoryController::class, 'storeDeviceSubCategory']);
        Route::apiResource('stocks', StockController::class);
    });




    // cashier Routes
    Route::group(['middleware' => ['role:cashier']], function () {

        Route::post('/cart/checkout', [CartController::class, 'checkout']);
    });
});
