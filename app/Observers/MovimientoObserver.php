<?php

namespace App\Observers;

use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\InvalidMovementTypeException;
use App\Exceptions\Stock\ProductNotFoundException;
use App\Exceptions\Stock\WarehouseNotFoundException;
use App\Models\Movimiento;
use App\Services\StockService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                // Cargar la relación 'movimientoProductos'
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->stockService->applyStockChanges($productoMovimiento);
                    // El registro en stock_movements ya se maneja dentro del StockService
                }
            });
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al procesar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            // Puedes decidir cómo manejar estas excepciones, por ejemplo, revertir la transacción o notificar al usuario.
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al procesar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
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
        if ($cambiosRelevantes->isNotEmpty()) {
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
            } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
                Log::error("Error específico al actualizar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
                throw $e;
            } catch (Exception $e) {
                Log::error("Error general al actualizar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
                throw $e;
            }
        }
    }

    /**
     * Handle the Movimiento "deleted" event.
     */
    public function deleted(Movimiento $movimiento): void
    {
        try {
            DB::transaction(function () use ($movimiento) {
                // Cargar la relación 'movimientoProductos'
                $movimiento->load('movimientoProductos');

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->stockService->revertProductMovementImpact($productoMovimiento);
                    // El registro en stock_movements ya se maneja dentro del StockService
                }
            });
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al eliminar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al eliminar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
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
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al restaurar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al restaurar el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
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
        } catch (InsufficientStockException|ProductNotFoundException|InvalidMovementTypeException|WarehouseNotFoundException $e) {
            Log::error("Error específico al eliminar forzosamente el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        } catch (Exception $e) {
            Log::error("Error general al eliminar forzosamente el movimiento ID: {$movimiento->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }
}
