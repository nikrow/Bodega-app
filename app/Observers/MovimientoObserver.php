<?php

namespace App\Observers;

use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoObserver
{
    /**
     * Handle the Movimiento "created" event.
     */
    public function created(Movimiento $movimiento): void
    {
        Log::info("Movimiento creado. ID: {$movimiento->id}");

        DB::transaction(function () use ($movimiento) {
            // Asegurarte de que la relación esté cargada
            $movimiento->load('movimientoProductos');

            foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                $this->applyProductMovementImpact($productoMovimiento);
            }
        });

    }

    /**
     * Handle the Movimiento "updated" event.
     */
    public function updated(Movimiento $movimiento): void
    {
        DB::transaction(function () use ($movimiento) {
            $oldMovement = $movimiento->getOriginal();
            if ($movimiento->relationLoaded('movimientoProductos')) {
                foreach ($oldMovement->movimientoProductos as $productoMovimiento) {
                    $this->revertProductMovementImpact($productoMovimiento);
                }

                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->applyProductMovementImpact($productoMovimiento);
                }
            } else {
                Log::error("No se cargaron los productos del movimiento para el movimiento ID: {$movimiento->id}");
            }
        });
    }

    /**
     * Handle the Movimiento "deleted" event.
     */
    public function deleted(Movimiento $movimiento): void
    {
        DB::transaction(function () use ($movimiento) {
            if ($movimiento->relationLoaded('movimientoProductos')) {
                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->revertProductMovementImpact($productoMovimiento);
                }
            } else {
                Log::error("No se cargaron los productos del movimiento para el movimiento ID: {$movimiento->id}");
            }
        });
    }

    /**
     * Handle the Movimiento "restored" event.
     */
    public function restored(Movimiento $movimiento): void
    {
        DB::transaction(function () use ($movimiento) {
            if ($movimiento->relationLoaded('movimientoProductos')) {
                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->applyProductMovementImpact($productoMovimiento);
                }
            } else {
                Log::error("No se cargaron los productos del movimiento para el movimiento ID: {$movimiento->id}");
            }
        });
    }

    /**
     * Handle the Movimiento "force deleted" event.
     */
    public function forceDeleted(Movimiento $movimiento): void
    {
        DB::transaction(function () use ($movimiento) {
            if ($movimiento->relationLoaded('movimientoProductos')) {
                foreach ($movimiento->movimientoProductos as $productoMovimiento) {
                    $this->revertProductMovementImpact($productoMovimiento);
                }
            } else {
                Log::error("No se cargaron los productos del movimiento para el movimiento ID: {$movimiento->id}");
            }
        });
    }

    /**
     * Revertir el impacto de un producto en el stock.
     */
    private function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        $stockOrigen = Stock::where([
            'product_id' => $productoMovimiento->producto_id,
            'field_id' => $productoMovimiento->movimiento->field_id,
            'wharehouse_id' => $productoMovimiento->movimiento->bodega_origen_id,
        ])->first();

        $stockDestino = Stock::where([
            'product_id' => $productoMovimiento->producto_id,
            'field_id' => $productoMovimiento->movimiento->field_id,
            'wharehouse_id' => $productoMovimiento->movimiento->bodega_destino_id,
        ])->first();

        if ($productoMovimiento->movimiento->tipo === 'entrada' && $stockDestino) {
            $stockDestino->quantity -= $productoMovimiento->cantidad;
            $stockDestino->save();
        } elseif ($productoMovimiento->movimiento->tipo === 'salida' && $stockOrigen) {
            $stockOrigen->quantity += $productoMovimiento->cantidad;
            $stockOrigen->save();
        } elseif ($productoMovimiento->movimiento->tipo === 'traslado') {
            if ($stockOrigen) {
                $stockOrigen->quantity += $productoMovimiento->cantidad;
                $stockOrigen->save();
            }
            if ($stockDestino) {
                $stockDestino->quantity -= $productoMovimiento->cantidad;
                $stockDestino->save();
            }
        }
    }

    /**
     * Aplicar el impacto de un producto en el stock.
     */
    private function applyProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        $stockOrigen = Stock::where([
            'producto_id' => $productoMovimiento->producto_id,
            'field_id' => $productoMovimiento->movimiento->field_id,
            'wharehouse_id' => $productoMovimiento->movimiento->bodega_origen_id,
        ])->first();

        $stockDestino = Stock::firstOrCreate([
            'product_id' => $productoMovimiento->producto_id,
            'field_id' => $productoMovimiento->movimiento->field_id,
            'wharehouse_id' => $productoMovimiento->movimiento->bodega_destino_id,
        ], [
            'quantity' => 0,
        ]);

        if ($productoMovimiento->movimiento->tipo === 'entrada') {
            $stockDestino->quantity += $productoMovimiento->cantidad;
            $stockDestino->save();
        } elseif ($productoMovimiento->movimiento->tipo === 'salida') {
            if ($stockOrigen && $stockOrigen->quantity >= $productoMovimiento->cantidad) {
                $stockOrigen->quantity -= $productoMovimiento->cantidad;
                $stockOrigen->save();
            } else {
                Log::error("Stock insuficiente para salida. Movimiento ID: {$productoMovimiento->movimiento->id}");
                throw new \Exception("Stock insuficiente en la bodega de origen.");
            }
        } elseif ($productoMovimiento->movimiento->tipo === 'traslado') {
            if ($stockOrigen && $stockOrigen->quantity >= $productoMovimiento->cantidad) {
                $stockOrigen->quantity -= $productoMovimiento->cantidad;
                $stockOrigen->save();

                $stockDestino->quantity += $productoMovimiento->cantidad;
                $stockDestino->save();
            } else {
                Log::error("Stock insuficiente para traslado. Movimiento ID: {$productoMovimiento->movimiento->id}");
                throw new \Exception("Stock insuficiente en la bodega de origen.");
            }
        }
    }
}
