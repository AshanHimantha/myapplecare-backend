<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\UserController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::post('/addProduct', [ProductController::class, 'store']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/product-images/{filename}', function ($filename) {
        $path = storage_path('app/public/products/' . $filename);
        if (!file_exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }
        return response()->file($path);
    });




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

        Route::get('/me', [AuthController::class, 'me']);
        Route::controller(UserController::class)->group(function () {
            Route::get('/users', 'index');
            Route::post('/users', 'store');
            Route::get('/users/{user}', 'show');
            Route::put('/users/{user}', 'update');
            Route::delete('/users/{user}', 'destroy');
            Route::post('/create-user', 'store');

        });





        Route::put('/updateProduct/{product}', [ProductController::class, 'update']);
        Route::delete('/deleteProduct/{product}', [ProductController::class, 'destroy']);


        // Device Categories
        Route::post('/device-categories', [CategoryController::class, 'storeDeviceCategory']);
        // Device Subcategories
        Route::post('/device-subcategories', [CategoryController::class, 'storeDeviceSubCategory']);
    });


    // cashier Routes
    Route::group(['middleware' => ['role:cashier']], function () {

        Route::controller(UserController::class)->group(function () {});
    });
});
