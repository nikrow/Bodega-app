<?php

namespace App\Observers;

use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\InvalidMovementTypeException;
use App\Exceptions\Stock\ProductNotFoundException;
use App\Exceptions\Stock\WarehouseNotFoundException;
use App\Models\MovimientoProducto;
use App\Services\StockService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            DB::transaction(function () use ($productoMovimiento) {
                $this->stockService->applyStockChanges($productoMovimiento);
                // El registro en stock_movements ya se maneja dentro del StockService
            });
            Log::info("Stock actualizado correctamente para el MovimientoProducto ID: {$productoMovimiento->id}");
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al aplicar cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al aplicar cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the MovimientoProducto "updated" event.
     */
    public function updated(MovimientoProducto $productoMovimiento): void
    {
        try {
            DB::transaction(function () use ($productoMovimiento) {
                $cantidadAnterior = $productoMovimiento->getOriginal('cantidad');
                $this->stockService->applyStockChanges($productoMovimiento, $cantidadAnterior);
            });
            Log::info("Stock actualizado correctamente para el MovimientoProducto ID: {$productoMovimiento->id}");
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al actualizar cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al actualizar cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the MovimientoProducto "deleted" event.
     */
    public function deleted(MovimientoProducto $productoMovimiento): void
    {
        try {
            DB::transaction(function () use ($productoMovimiento) {
                // Revertir el impacto en el stock
                $this->stockService->revertProductMovementImpact($productoMovimiento);

                // Eliminar los StockMovements asociados
                $productoMovimiento->stockMovements()->delete();

                Log::info("StockService: Impacto revertido y StockMovements eliminados para MovimientoProducto ID: {$productoMovimiento->id}");
            });
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al revertir cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al revertir cambios de stock para MovimientoProducto ID: {$productoMovimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }
}
