<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/create-user', [UserController::class, 'store']);


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
    });


    // cashier Routes
    Route::group(['middleware' => ['role:cashier']], function () {

        Route::controller(UserController::class)->group(function () {


        });
    });

});



