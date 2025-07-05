<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\InvoiceItemController;
use App\Http\Controllers\API\PartController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\RepairController;
use App\Http\Controllers\API\StockController;
use App\Http\Controllers\API\TicketController;
use App\Http\Controllers\API\TicketItemController;
use App\Http\Controllers\API\UserController;


// Public Routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('invoices/{id}', [InvoiceController::class, 'show']);

// Route::get('/part-images/{filename}', function ($filename) {
//     $path = storage_path('app/public/parts/' . $filename);
//     if (!file_exists($path)) {
//         return response()->json(['message' => 'Image not found'], 404);
//     }
//     return response()->file($path);
// });

Route::get('invoices-search', [InvoiceController::class, 'search']);


Route::get('create-storage-link', function () {
    Artisan::call('storage:link');
    return response()->json(['message' => 'Storage link created successfully']);
});

Route::get('clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    return response()->json(['message' => 'All caches cleared successfully']);
});


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {


    Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/me', [AuthController::class, 'me']);
Route::get('invoices', [InvoiceController::class, 'index']);
Route::get('invoices/daily', [InvoiceController::class, 'daily']);

    Route::get('/users', [UserController::class, 'index']);

    Route::get('stocks/available', [StockController::class, 'available']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::get('/product-images/{filename}', function ($filename) {
    //     $path = storage_path('app/public/products/' . $filename);
    //     if (!file_exists($path)) {
    //         return response()->json(['message' => 'Image not found'], 404);
    //     }
    //     return response()->file($path);
    // });


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
    // Route::get('/storage/products/{filename}', function ($filename) {
    //     $path = storage_path('app/public/products/' . $filename);
    //     if (!file_exists($path)) {
    //         return response()->json(['message' => 'Image not found'], 404);
    //     }
    //     return response()->file($path);
    // });


    // Admin Routes
    Route::group(['middleware' => ['role:admin']], function () {

        Route::controller(UserController::class)->group(function () {
           
            Route::post('/users', 'store');
            Route::get('/users/{user}', 'show');
            Route::put('/users/{user}', 'update');
            Route::delete('/users/{user}', 'destroy');
        });

        Route::post('/addProduct', [ProductController::class, 'store']);
        Route::put('/updateProduct/{product}', [ProductController::class, 'update']);
        Route::delete('/deleteProduct/{product}', [ProductController::class, 'destroy']);
        Route::post('/device-categories', [CategoryController::class, 'storeDeviceCategory']);
        Route::post('/device-subcategories', [CategoryController::class, 'storeDeviceSubCategory']);
        Route::apiResource('stocks', StockController::class);
        Route::get('stocks-search', [StockController::class, 'search']);
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus']);
        Route::apiResource('repairs', RepairController::class);
        Route::get('returned-items', [InvoiceController::class, 'returnedItems']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
        Route::get('/dashboard/service-metrics', [DashboardController::class, 'serviceMetrics']);
        Route::get('/dashboard/ticket-charts', [DashboardController::class, 'ticketCharts']);
        Route::get('/dashboard/sales-metrics', [DashboardController::class, 'SalesOutlet']);
        
    });


    // cashier Routes
    Route::group(['middleware' => ['role:cashier']], function () {
        Route::post('/cart/checkout', [CartController::class, 'checkout']);
        Route::apiResource('invoice-items', InvoiceItemController::class);
        Route::post('invoices/return', [InvoiceController::class, 'processReturn']);
    });


    Route::group(['middleware' => ['role:technician']], function () {

        Route::delete('tickets/{id}', [TicketController::class, 'destroy']);
        Route::apiResource('tickets', TicketController::class);
        Route::get('tickets-filter', [TicketController::class, 'filter']);
        Route::apiResource('parts', PartController::class);
        Route::get('tickets-search', [TicketController::class, 'search']);
        Route::get('parts-search', [PartController::class, 'search']);
        Route::apiResource('repairs', RepairController::class);
        Route::get('repairs-search', [RepairController::class, 'search']);
        Route::apiResource('ticket-items', TicketItemController::class);
        Route::get('tickets/{ticket_id}/items', [TicketItemController::class, 'getTicketItems']);
    });
});
