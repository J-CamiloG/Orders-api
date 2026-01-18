<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Orders API Routes
Route::prefix('orders')->group(function () {
    // Import orders from file
    Route::post('/import', [OrderController::class, 'import']);
    
    // Import orders from JSON payload (Swagger friendly)
    Route::post('/import-json', [OrderController::class, 'importJson']);
    
    // List all orders
    Route::get('/', [OrderController::class, 'index']);
    
    // Get specific order
    Route::get('/{id}', [OrderController::class, 'show']);
    
    // Get order status with history
    Route::get('/{id}/status', [OrderController::class, 'status']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Laravel Orders API',
        'timestamp' => now()
    ]);
});
