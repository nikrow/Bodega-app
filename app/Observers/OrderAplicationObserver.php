<?php

namespace App\Observers;

use App\Models\OrderAplication;
use App\Models\OrderApplicationUsage;
use Illuminate\Support\Facades\Log;

class OrderAplicationObserver
{
    /**
     * Handle the OrderAplication "created" event.
     */
    public function created(OrderAplication $orderAplication): void
    {
        Log::info('OrderAplication created: ' . $orderAplication->id);

        try {
            if ($orderAplication->order && $orderAplication->order->orderLines) {
                foreach ($orderAplication->order->orderLines as $orderLine) {
                    $dosis = $orderLine->dosis ?? 0;
                    $liter = $orderAplication->liter ?? 0;

                    Log::info('Processing OrderLine ID: ' . $orderLine->id . ' with dosis: ' . $dosis . ' and liters applied: ' . $liter);

                    // Asegurarse de que la dosis y los litros aplicados son mayores a cero antes de crear el registro
                    if ($dosis > 0 && $liter > 0) {
                        $productUsage = ($liter * $dosis) / 100;

                        Log::info('Calculated product usage: ' . $productUsage);

                        OrderApplicationUsage::create([
                            'field_id' => $orderAplication->order->field_id,
                            'order_id' => $orderAplication->order->id,
                            'orderNumber' => $orderAplication->order->orderNumber,
                            'parcel_id' => $orderAplication->parcel_id,
                            'product_id' => $orderLine->product_id,
                            'liters_applied' => $liter,
                            'dose_per_100l' => $dosis,
                            'product_usage' => $productUsage,
                        ]);

                        Log::info('OrderApplicationUsage created for OrderAplication ID: ' . $orderAplication->id);
                    } else {
                        Log::warning('OrderApplicationUsage not created due to invalid dosis or liter value for OrderAplication ID: ' . $orderAplication->id);
                    }
                }
            } else {
                Log::warning('Order or OrderLines not available for OrderAplication ID: ' . $orderAplication->id);
            }
        } catch (\Exception $e) {
            Log::error('Error while creating OrderApplicationUsage for OrderAplication ID: ' . $orderAplication->id . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the OrderAplication "updated" event.
     */
    public function updated(OrderAplication $orderAplication): void
    {
        Log::info('OrderAplication updated: ' . $orderAplication->id);

        try {
            $order = $orderAplication->order;

            if ($order && $order->status === 'Pendiente') {
                $order->update(['status' => 'En Proceso']);
                Log::info('Order status updated to "En Proceso" for Order ID: ' . $order->id);
            }

            // Actualizar registros en la tabla de uso de aplicación
            if ($order && $order->orderLines) {
                foreach ($order->orderLines as $orderLine) {
                    $dosis = $orderLine->dosis ?? 0;
                    $liter = $orderAplication->liter ?? 0;

                    Log::info('Updating OrderLine ID: ' . $orderLine->id . ' with dosis: ' . $dosis . ' and liters applied: ' . $liter);

                    if ($dosis > 0 && $liter > 0) {
                        $productUsage = ($liter * $dosis) / 100;

                        Log::info('Calculated product usage for update: ' . $productUsage);

                        OrderApplicationUsage::updateOrCreate(
                            [
                                'order_id' => $orderAplication->order->id,
                                'parcel_id' => $orderAplication->parcel_id,
                                'product_id' => $orderLine->product_id,
                            ],
                            [
                                'field_id' => $orderAplication->order->field_id,
                                'orderNumber' => $orderAplication->order->orderNumber,
                                'liters_applied' => $liter,
                                'dose_per_100l' => $dosis,
                                'product_usage' => $productUsage,
                            ]
                        );

                        Log::info('OrderApplicationUsage updated or created for OrderAplication ID: ' . $orderAplication->id);
                    } else {
                        Log::warning('OrderApplicationUsage not updated due to invalid dosis or liter value for OrderAplication ID: ' . $orderAplication->id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error while updating OrderApplicationUsage for OrderAplication ID: ' . $orderAplication->id . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the OrderAplication "deleted" event.
     */
    public function deleted(OrderAplication $orderAplication): void
    {
        Log::info('OrderAplication deleted: ' . $orderAplication->id);

        try {
            // Eliminar los registros correspondientes en la tabla de uso de aplicación
            $deleted = OrderApplicationUsage::where('order_id', $orderAplication->order->id)
                ->where('parcel_id', $orderAplication->parcel_id)
                ->delete();

            if ($deleted) {
                Log::info('OrderApplicationUsage records deleted for OrderAplication ID: ' . $orderAplication->id);
            } else {
                Log::warning('No OrderApplicationUsage records found to delete for OrderAplication ID: ' . $orderAplication->id);
            }
        } catch (\Exception $e) {
            Log::error('Error while deleting OrderApplicationUsage for OrderAplication ID: ' . $orderAplication->id . '. Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the OrderAplication "restored" event.
     */
    public function restored(OrderAplication $orderAplication): void
    {
        Log::info('OrderAplication restored: ' . $orderAplication->id);
    }

    /**
     * Handle the OrderAplication "force deleted" event.
     */
    public function forceDeleted(OrderAplication $orderAplication): void
    {
        Log::info('OrderAplication force deleted: ' . $orderAplication->id);
    }
}
