<?php

namespace App\Observers;

use App\Models\MovimientoProducto;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;

class MovimientoProductoObserver
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Cuando se crea un MovimientoProducto, actualizar stock y crear logs.
     */
    public function created(MovimientoProducto $productoMovimiento): void
    {
        try {
            // Al crearse, actualiza stock, crea StockMovement y StockHistory
            $this->stockService->applyStockChanges($productoMovimiento);
            Log::info("MovimientoProducto ID {$productoMovimiento->id} creado y stock actualizado.");
        } catch (\Exception $e) {
            Log::error("Error al crear MovimientoProducto ID {$productoMovimiento->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Cuando se actualiza (por ejemplo, se cambia la cantidad).
     */
    public function updated(MovimientoProducto $productoMovimiento): void
    {
        $cantidadAnterior = $productoMovimiento->getOriginal('cantidad');

        try {
            $this->stockService->applyStockChanges($productoMovimiento, $cantidadAnterior);
            Log::info("MovimientoProducto ID {$productoMovimiento->id} actualizado y stock ajustado.");
        } catch (\Exception $e) {
            Log::error("Error al actualizar MovimientoProducto ID {$productoMovimiento->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Cuando se elimina un MovimientoProducto, revertir stock y borrar logs.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        try {
            // 1. Revertir el impacto (actualiza stock de vuelta)
            $this->stockService->revertProductMovementImpact($productoMovimiento);

            // 2. Eliminar StockMovement(s) relacionados
            $productoMovimiento->stockMovements()->delete();

            // 3. Eliminar StockHistories relacionados (si quieres borrar el historial).
            //    Si tu relación en StockHistory apunta a movement_product_id
            //    podrías hacer:
            \App\Models\StockHistory::where('movement_product_id', $productoMovimiento->id)->delete();

            Log::info("MovimientoProducto ID {$productoMovimiento->id} eliminado, stock revertido, StockMovement y StockHistory borrados.");
        } catch (\Exception $e) {
            Log::error("Error al eliminar MovimientoProducto ID {$productoMovimiento->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
