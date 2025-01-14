<?php

namespace App\Observers;

use App\Models\MovimientoProducto;
use App\Models\StockMovement;
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
            Log::info("MovimientoProducto ID $productoMovimiento->id creado y stock actualizado.");
        } catch (\Exception $e) {
            Log::error("Error al crear MovimientoProducto ID $productoMovimiento->id: {$e->getMessage()}");
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
            Log::info("MovimientoProducto ID $productoMovimiento->id actualizado y stock ajustado.");
        } catch (\Exception $e) {
            Log::error("Error al actualizar MovimientoProducto ID $productoMovimiento->id: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Cuando se elimina un MovimientoProducto, revertir stock y borrar logs.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        try {
            // Revertir el impacto del movimiento eliminado en el stock
            $this->stockService->revertProductMovementImpact($productoMovimiento);

            // Eliminar el registro del movimiento de stock asociado
            StockMovement::where('related_id', $productoMovimiento->id)
                ->where('related_type', MovimientoProducto::class)
                ->delete();

            Log::info("MovimientoProducto ID {$productoMovimiento->id} eliminado y stock actualizado correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al eliminar MovimientoProducto ID {$productoMovimiento->id}: {$e->getMessage()}");
            throw $e;
        }
    }


}
