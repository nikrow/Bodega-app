<?php

namespace App\Observers;

use App\Models\OrderApplicationUsage;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderApplicationUsageObserver
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the OrderApplicationUsage "created" event.
     */
    public function created(OrderApplicationUsage $usage): void
    {
        try {
            // Carga las relaciones necesarias
            $usage->load('orderApplication.order');

            $warehouseId = $usage->orderApplication->order->warehouse_id;

            // Deduce el stock basado en el uso de la aplicación
            $this->stockService->deductUsageStock(
                $usage->product_id,
                $warehouseId,
                $usage->product_usage
            );
            Log::info("Stock descontado correctamente para OrderApplicationUsage ID: {$usage->id}");

            // Registrar en stock_movements
            $this->stockService->logUsageMovement(
                $usage,
                'application_usage',
                -$usage->product_usage,
                "Litros aplicados: {$usage->liters_applied}, Dosis: {$usage->dose_per_100l}."
            );
        } catch (Exception $e) {
            Log::error("Error al descontar stock para OrderApplicationUsage ID: {$usage->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the OrderApplicationUsage "updated" event.
     */
    public function updated(OrderApplicationUsage $usage): void
    {
        try {
            // Carga las relaciones necesarias
            $usage->load('orderApplication.order');

            $originalUsage = $usage->getOriginal('product_usage');
            $newUsage = $usage->product_usage;
            $difference = $newUsage - $originalUsage;

            if ($difference != 0) {
                $warehouseId = $usage->orderApplication->order->warehouse_id;

                if ($difference > 0) {
                    // Mayor uso, deduce más stock
                    $this->stockService->deductUsageStock(
                        $usage->product_id,
                        $warehouseId,
                        $difference
                    );
                    Log::info("Stock descontado por diferencia positiva para OrderApplicationUsage ID: {$usage->id}");

                    // Registrar en stock_movements
                    $this->stockService->logUsageMovement(
                        $usage,
                        'application_usage_update',
                        -$difference,
                        "Incremento en uso: {$difference}."
                    );
                } else {
                    // Menor uso, reponer stock
                    $this->stockService->revertUsageStock(
                        $usage->product_id,
                        $warehouseId,
                        abs($difference)
                    );
                    Log::info("Stock incrementado por diferencia negativa para OrderApplicationUsage ID: {$usage->id}");

                    // Registrar en stock_movements
                    $this->stockService->logUsageMovement(
                        $usage,
                        'application_usage_update',
                        abs($difference),
                        "Reducción en uso: {$difference}."
                    );
                }
            }
        } catch (Exception $e) {
            Log::error("Error al actualizar stock para OrderApplicationUsage ID: {$usage->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle the OrderApplicationUsage "deleted" event.
     */
    public function deleted(OrderApplicationUsage $usage): void
    {
        try {
            // Carga las relaciones necesarias
            $usage->load('orderApplication.order');

            $warehouseId = $usage->orderApplication->order->warehouse_id;

            // Revertir el stock deducido por el uso de la aplicación
            $this->stockService->revertUsageStock(
                $usage->product_id,
                $warehouseId,
                $usage->product_usage
            );
            Log::info("Stock revertido correctamente para OrderApplicationUsage ID: {$usage->id}");

            // Registrar en stock_movements
            $this->stockService->logUsageMovement(
                $usage,
                'application_usage_deleted',
                $usage->product_usage,
                "Uso de aplicación eliminado. Stock revertido."
            );
        } catch (Exception $e) {
            Log::error("Error al revertir stock para OrderApplicationUsage ID: {$usage->id}. Error: {$e->getMessage()}");
            throw $e;
        }
    }
}
