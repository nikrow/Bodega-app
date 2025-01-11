<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\OrderController;
use App\Http\Controllers\WaterLiveController;

Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/download-pdf', [OrderController::class, 'downloadPdf'])->name('orders.downloadPdf');
    Route::get('/orders/{order}/bodega-pdf', [OrderController::class, 'bodegaPdf'])->name('orders.bodegaPdf');
});
Route::get('/riego-live', [WaterLiveController::class, 'index']);

