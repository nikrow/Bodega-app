<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportExportController;


use App\Http\Controllers\OrderController;

Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/download-pdf', [OrderController::class, 'downloadPdf'])->name('orders.downloadPdf');
    Route::get('/orders/{order}/bodega-pdf', [OrderController::class, 'bodegaPdf'])->name('orders.bodegaPdf');
});
Route::get('/reportes/exportar/excel', [ReportExportController::class, 'exportExcel'])->name('reportes.exportar.excel');
