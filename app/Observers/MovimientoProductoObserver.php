<?php

namespace App\Observers;

use App\Models\MovimientoProducto;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;
use Exception;

class MovimientoProductoObserver
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the MovimientoProducto "created" event.
     */
    public function created(MovimientoProducto $productoMovimiento): void
    {
        // Permitir que las excepciones se propaguen
        $this->stockService->applyStockChanges($productoMovimiento);
        Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
    }

    /**
     * Handle the MovimientoProducto "updated" event.
     */
    public function updated(MovimientoProducto $productoMovimiento): void
    {
        // Permitir que las excepciones se propaguen
        $original = MovimientoProducto::find($productoMovimiento->id);
        $this->stockService->revertProductMovementImpact($original);
        $this->stockService->applyStockChanges($productoMovimiento);
        Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
    }

    /**
     * Handle the MovimientoProducto "deleted" event.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        // Permitir que las excepciones se propaguen
        $this->stockService->revertProductMovementImpact($productoMovimiento);
        Log::info("Stock revertido correctamente para el producto ID: {$productoMovimiento->producto_id}");
    }
}
