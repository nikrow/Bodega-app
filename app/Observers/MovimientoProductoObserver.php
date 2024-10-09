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
        try {
            // Aplicar cambios de stock cuando se cree un nuevo MovimientoProducto
            $this->stockService->applyStockChanges($productoMovimiento);
            Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
        } catch (Exception $e) {
            Log::error("Error al actualizar el stock para el producto ID: {$productoMovimiento->producto_id}: " . $e->getMessage());
        }
    }

    /**
     * Handle the MovimientoProducto "updated" event.
     */
    public function updated(MovimientoProducto $productoMovimiento): void
    {
        try {
            // Revertir el stock anterior antes de aplicar los nuevos cambios
            $original = $productoMovimiento->getOriginal();
            $this->stockService->revertProductMovementImpact($original);

            // Aplicar los nuevos cambios
            $this->stockService->applyStockChanges($productoMovimiento);
            Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
        } catch (Exception $e) {
            Log::error("Error al actualizar el stock para el producto ID: {$productoMovimiento->producto_id}: " . $e->getMessage());
        }
    }

    /**
     * Handle the MovimientoProducto "deleted" event.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        try {
            // Revertir el impacto en el stock al eliminar un producto
            $this->stockService->revertProductMovementImpact($productoMovimiento);
            Log::info("Stock revertido correctamente para el producto ID: {$productoMovimiento->producto_id}");
        } catch (Exception $e) {
            Log::error("Error al revertir el stock para el producto ID: {$productoMovimiento->producto_id}: " . $e->getMessage());
        }
    }
}
