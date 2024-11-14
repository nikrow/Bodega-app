<?php

namespace App\Observers;

use App\Models\MovimientoProducto;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
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
            $this->stockService->applyStockChanges($productoMovimiento);
            Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
        } catch (Exception $e) {
            Log::error("Error al aplicar cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e; // Opcional: lanzar excepción para revertir la transacción
        }
    }

    /**
     * Handle the MovimientoProducto "updated" event.
     */
    public function updated(MovimientoProducto $productoMovimiento): void
    {
        try {
            $cantidadAnterior = $productoMovimiento->getOriginal('cantidad');
            $this->stockService->applyStockChanges($productoMovimiento, $cantidadAnterior);
            Log::info("Stock actualizado correctamente para el producto ID: {$productoMovimiento->producto_id}");
        } catch (Exception $e) {
            Log::error("Error al actualizar cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the MovimientoProducto "deleted" event.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        try {
            // Iniciar una transacción para asegurar la consistencia
            DB::transaction(function () use ($productoMovimiento) {
                    // Revertir el impacto en el stock
                    $this->stockService->revertProductMovementImpact($productoMovimiento);

                    // Eliminar los StockMovements asociados
                    $productoMovimiento->stockMovements()->delete();

                    Log::info("StockService: Impacto revertido y StockMovements eliminados para MovimientoProducto ID: {$productoMovimiento->id}");
                });
        } catch (Exception $e) {
            Log::error("Error al revertir cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

}
