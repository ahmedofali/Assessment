<?php

use App\Http\Controllers\Storefront\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')
    ->name('orders.')
    ->controller(OrderController::class)
    ->group(function () {
        Route::post('/', 'store');
//            ->middleware('idempotent');
});
