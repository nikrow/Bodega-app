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
        // Obtener la cantidad anterior antes de la actualización
        $cantidadAnterior = $productoMovimiento->getOriginal('cantidad');

        // Revertir el impacto anterior en el stock
        $this->stockService->applyStockChanges($productoMovimiento, $cantidadAnterior);

        Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
    }

    /**
     * Handle the MovimientoProducto "deleted" event.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        // Revertir el impacto del movimiento cuando se borra
        $this->stockService->revertProductMovementImpact($productoMovimiento);
        Log::info("Stock revertido correctamente para el producto ID: {$productoMovimiento->producto_id}");
    }
}
