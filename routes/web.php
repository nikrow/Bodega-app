<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\OrderController;

Route::get('/orders/{order}/download-pdf', [OrderController::class, 'downloadPdf'])->name('orders.downloadPdf');
