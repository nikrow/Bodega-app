<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ParcelExportController;
use App\Http\Controllers\ReportExportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/download-pdf', [OrderController::class, 'downloadPdf'])->name('orders.downloadPdf');
    Route::get('/orders/{order}/bodega-pdf', [OrderController::class, 'bodegaPdf'])->name('orders.bodegaPdf');
});
Route::get('/reportes/exportar/excel', [ReportExportController::class, 'exportExcel'])->name('reportes.exportar.excel');
Route::get('/cuarteles/exportar/excel', [ParcelExportController::class, 'exportExcel'])->name('cuarteles.exportar.excel');