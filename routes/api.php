<?php

use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\SalesReportController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('tokens', [TokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('tokens.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');

        Route::get('me', fn (Request $request) => $request->user()?->only('id', 'name', 'email', 'role'))->name('me');

        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
        Route::patch('orders/{order}/status', OrderStatusController::class)->name('orders.status');

        Route::get('reports/sales', SalesReportController::class)->name('reports.sales');
    });
});
