<?php

namespace App\Observers;

use App\Models\OrderApplication;
use App\Models\OrderApplicationUsage;
use Illuminate\Support\Facades\Log;

class OrderApplicationObserver
{
    /**
     * Handle the OrderApplication "created" event.
     */
    public function created(OrderApplication $orderApplication): void
    {
        Log::info('OrderApplication created: ' . $orderApplication->id);

        try {
            if ($orderApplication->order && $orderApplication->order->orderLines) {
                foreach ($orderApplication->order->orderLines as $orderLine) {
                    $dosis = $orderLine->dosis ?? 0;
                    $liter = $orderApplication->liter ?? 0;

                    Log::info('Processing OrderLine ID: ' . $orderLine->id . ' with dosis: ' . $dosis . ' and liters applied: ' . $liter);

                    // Asegurarse de que la dosis y los litros aplicados son mayores a cero antes de crear el registro
                    if ($dosis > 0 && $liter > 0) {
                        $productUsage = ($liter * $dosis) / 100;

                        Log::info('Calculated product usage: ' . $productUsage);

                        OrderApplicationUsage::create([
                            'field_id' => $orderApplication->order->field_id,
                            'order_application_id' => $orderApplication->id,
                            'order_id' => $orderApplication->order->id,
                            'orderNumber' => $orderApplication->order->orderNumber,
                            'parcel_id' => $orderApplication->parcel_id,
                            'product_id' => $orderLine->product_id,
                            'liters_applied' => $liter,
                            'dose_per_100l' => $dosis,
                            'product_usage' => $productUsage,
                        ]);

                        Log::info('OrderApplicationUsage created for OrderApplication ID: ' . $orderApplication->id);
                    } else {
                        Log::warning('OrderApplicationUsage not created due to invalid dosis or liter value for OrderApplication ID: ' . $orderApplication->id);
                    }
                }
            } else {
                Log::warning('Order or OrderLines not available for OrderApplication ID: ' . $orderApplication->id);
            }
        } catch (\Exception $e) {
            Log::error('Error while creating OrderApplicationUsage for OrderApplication ID: ' . $orderApplication->id . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the OrderApplication "updated" event.
     */
    public function updated(OrderApplication $orderApplication): void
    {
        Log::info('OrderApplication updated: ' . $orderApplication->id);

        try {
            $order = $orderApplication->order;

            if ($order && $order->status === 'Pendiente') {
                $order->update(['status' => 'En Proceso']);
                Log::info('Order status updated to "En Proceso" for Order ID: ' . $order->id);
            }

            // Actualizar registros en la tabla de uso de aplicación
            if ($order && $order->orderLines) {
                foreach ($order->orderLines as $orderLine) {
                    $dosis = $orderLine->dosis ?? 0;
                    $liter = $orderApplication->liter ?? 0;

                    Log::info('Updating OrderLine ID: ' . $orderLine->id . ' with dosis: ' . $dosis . ' and liters applied: ' . $liter);

                    if ($dosis > 0 && $liter > 0) {
                        $productUsage = ($liter * $dosis) / 100;

                        Log::info('Calculated product usage for update: ' . $productUsage);

                        OrderApplicationUsage::updateOrCreate(
                            [
                                'order_application_id' => $orderApplication->id, // <-- Agregar esta línea
                                'order_id' => $orderApplication->order->id,
                                'parcel_id' => $orderApplication->parcel_id,
                                'product_id' => $orderLine->product_id,
                            ],
                            [
                                'field_id' => $orderApplication->order->field_id,
                                'orderNumber' => $orderApplication->order->orderNumber,
                                'liters_applied' => $liter,
                                'dose_per_100l' => $dosis,
                                'product_usage' => $productUsage,
                            ]
                        );

                        Log::info('OrderApplicationUsage updated or created for OrderApplication ID: ' . $orderApplication->id);
                    } else {
                        Log::warning('OrderApplicationUsage not updated due to invalid dosis or liter value for OrderApplication ID: ' . $orderApplication->id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error while updating OrderApplicationUsage for OrderApplication ID: ' . $orderApplication->id . '. Error: ' . $e->getMessage());
        }
    }


    /**
     * Handle the OrderApplication "deleted" event.
     */
    public function deleted(OrderApplication $orderApplication): void
    {
        Log::info('OrderApplication deleted: ' . $orderApplication->id);

        try {
            // Eliminar los registros correspondientes en la tabla de uso de aplicación
            $deleted = OrderApplicationUsage::where('order_id', $orderApplication->order->id)
                ->where('parcel_id', $orderApplication->parcel_id)
                ->delete();

            if ($deleted) {
                Log::info('OrderApplicationUsage records deleted for OrderApplication ID: ' . $orderApplication->id);
            } else {
                Log::warning('No OrderApplicationUsage records found to delete for OrderApplication ID: ' . $orderApplication->id);
            }
        } catch (\Exception $e) {
            Log::error('Error while deleting OrderApplicationUsage for OrderApplication ID: ' . $orderApplication->id . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the OrderApplication "restored" event.
     */
    public function restored(OrderApplication $orderApplication): void
    {
        Log::info('OrderApplication restored: ' . $orderApplication->id);
    }

    /**
     * Handle the OrderApplication "force deleted" event.
     */
    public function forceDeleted(OrderApplication $orderApplication): void
    {
        Log::info('OrderApplication force deleted: ' . $orderApplication->id);
    }
}
