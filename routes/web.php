<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ParcelExportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\FertilizationExportController;
use App\Http\Controllers\StockMovementExportController;
use App\Http\Controllers\ApplicationRecordExportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/download-pdf', [OrderController::class, 'downloadPdf'])->name('orders.downloadPdf');
    Route::get('/orders/{order}/bodega-pdf', [OrderController::class, 'bodegaPdf'])->name('orders.bodegaPdf');
});
Route::get('/reportes/exportar/excel', [ReportExportController::class, 'exportExcel'])->name('reportes.exportar.excel');
Route::get('/cuarteles/exportar/excel', [ParcelExportController::class, 'exportExcel'])->name('cuarteles.exportar.excel');
Route::get('/aplicaciones/exportar/excel', [ApplicationRecordExportController::class, 'exportExcel'])->name('export.application-records');
Route::get('movimientos-stock/exportar/excel', [StockMovementExportController::class, 'exportExcel'])->name('movimientos-stock.exportar.excel');
Route::get('fertilizaciones/exportar/excel', [FertilizationExportController::class, 'exportExcel'])->name('fertilizations.exportar.excel');