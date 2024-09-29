<?php

namespace App\Observers;

use App\Models\Movement;
use App\Models\Stock;

class MovementObserver
{
    /**
     * Handle the Movement "created" event.
     */
    public function created(Movement $movement): void
    {
        $stockDestino = Stock::firstOrCreate([
            'product_id' => $movement->product_id,
            'field_id' => $movement->field_id,
            'wharehouse_id' => $movement->bodega_destino_id,
        ]);
        if ($movement->tipo === 'entrada') {
            $stock->quantity += $movement->cantidad;
        } elseif ($movement->tipo === 'salida') {
            $stock->quantity -= $movement->cantidad;
        }
        elseif ($movement->tipo === 'traslado') {
            $stockOrigen = Stock::where([
                'product_id'=> $movement->product_id,
                'field_id'=> $movement->field_id,
                'wharehouse_id'=> $movement->bodega_origen_id,
            ])->first();

            if ($stockOrigen) {
                $stockOrigen->quantity -= $movement->cantidad;
                $stockOrigen->save();
            }

            $stockDestino ->quantity += $movement->cantidad;
        }
        $stockDestino->save();
    }

    /**
     * Handle the Movement "updated" event.
     */
    public function updated(Movement $movement): void
    {
        //
    }

    /**
     * Handle the Movement "deleted" event.
     */
    public function deleted(Movement $movement): void
    {
        //
    }

    /**
     * Handle the Movement "restored" event.
     */
    public function restored(Movement $movement): void
    {
        //
    }

    /**
     * Handle the Movement "force deleted" event.
     */
    public function forceDeleted(Movement $movement): void
    {
        //
    }
}
