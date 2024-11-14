<?php

namespace App\Observers;

use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MovimientoObserver
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the Movimiento "created" event.
     */
    public function created(Movimiento $movimiento): void
    {
        Log::info("Movimiento creado. ID: {$movimiento->id}");

        try {
            DB::transaction(function () use ($movimiento) {
                // Asegurarte de que la relación esté cargada
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->stockService->applyStockChanges($productoMovimiento);
                    // El registro en stock_movements ya se maneja dentro del StockService
                }
            });
        } catch (Exception $e) {
            Log::error("Error al procesar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the Movimiento "updated" event.
     */
    public function updated(Movimiento $movimiento): void
    {
        // Definir los campos que deben ser ignorados al verificar cambios
        $ignorarCampos = ['is_completed', 'updated_at', 'updated_by'];

        // Obtener los campos que han cambiado excluyendo los campos a ignorar
        $cambiosRelevantes = collect($movimiento->getChanges())->except($ignorarCampos);

        // Verificar si solo se ha cambiado 'is_completed' y los campos a ignorar
        if ($movimiento->wasChanged('is_completed') && $cambiosRelevantes->isEmpty()) {
            Log::info("Movimiento ID: {$movimiento->id} ha sido marcado como completado. No se aplicarán cambios en el stock.");
            return; // Salir sin aplicar cambios en el stock
        }

        // Aplicar cambios en el stock si hay cambios relevantes
        try {
            DB::transaction(function () use ($movimiento) {
                // Cargar las relaciones necesarias
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $cantidadAnterior = $productoMovimiento->getOriginal('cantidad');
                    $this->stockService->applyStockChanges($productoMovimiento, $cantidadAnterior);
                }
            });

            Log::info("MovimientoObserver: Cambios de stock aplicados correctamente para Movimiento ID: {$movimiento->id}");
        } catch (Exception $e) {
            Log::error("MovimientoObserver: Error al actualizar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }


    /**
     * Handle the Movimiento "deleted" event.
     */
    public function deleted(Movimiento $movimiento): void
    {
        try {
            DB::transaction(function () use ($movimiento) {
                // Asegurarte de que la relación esté cargada
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->stockService->revertProductMovementImpact($productoMovimiento);
                    // El registro en stock_movements ya se maneja dentro del StockService
                }
            });
        } catch (Exception $e) {
            Log::error("Error al eliminar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the Movimiento "restored" event.
     */
    public function restored(Movimiento $movimiento): void
    {
        try {
            DB::transaction(function () use ($movimiento) {
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->stockService->applyStockChanges($productoMovimiento);
                    // El registro en stock_movements ya se maneja dentro del StockService
                }
            });
        } catch (Exception $e) {
            Log::error("Error al restaurar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the Movimiento "force deleted" event.
     */
    public function forceDeleted(Movimiento $movimiento): void
    {
        try {
            DB::transaction(function () use ($movimiento) {
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->stockService->revertProductMovementImpact($productoMovimiento);
                    // El registro en stock_movements ya se maneja dentro del StockService
                }
            });
        } catch (Exception $e) {
            Log::error("Error al eliminar forzosamente el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }
}
