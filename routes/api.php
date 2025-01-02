<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\UserController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);






// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);


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

        Route::post('/addProduct', [ProductController::class, 'store']);
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
