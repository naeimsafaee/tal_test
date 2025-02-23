<?php

use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('api')->group(function () {
    // Endpoint to create a new order.
    Route::post('/orders', [OrderController::class, 'store']);

    // Endpoint to list all orders and trades with pagination.
    Route::get('/orders', [OrderController::class, 'index']);

    // Endpoint to cancel an open order.
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
