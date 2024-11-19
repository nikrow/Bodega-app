<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Models\OrderApplicationUsage;
use App\Models\Stock;
use App\Models\StockMovement;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockService
{
    /**
     * Aplicar cambios de stock basados en un movimiento de producto.
     *
     * @param MovimientoProducto $productoMovimiento
     * @param int|null $cantidadAnterior
     * @return void
     * @throws Exception
     */
    public function applyStockChanges(MovimientoProducto $productoMovimiento, ?int $cantidadAnterior = null): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = strtolower($movimiento->tipo->value);
        $cantidadNueva = $productoMovimiento->cantidad;

        $userId = Auth::id();

        // Obtener el producto asociado al movimiento
        $producto = $productoMovimiento->producto;
        if (!$producto) {
            Log::error('Producto no encontrado para el movimientoProducto ID: ' . $productoMovimiento->id);
            throw new Exception('Producto no encontrado.');
        }

        // Revertir el impacto anterior del stock (si la cantidad anterior existe)
        if ($cantidadAnterior !== null) {
            $this->revertPreviousImpact($tipo, $movimiento, $productoMovimiento, $cantidadAnterior);
        }

        // Aplicar el nuevo impacto de stock
        $this->applyNewImpact($tipo, $movimiento, $productoMovimiento, $cantidadNueva, $producto, $userId);
    }


    /**
     * Revertir el impacto anterior en el stock.
     *
     * @param string $tipo
     * @param Movimiento $movimiento
     * @param MovimientoProducto $productoMovimiento
     * @param int $cantidadAnterior
     * @return void
     */
    private function revertPreviousImpact(string $tipo, Movimiento $movimiento, MovimientoProducto $productoMovimiento, int $cantidadAnterior): void
    {
        switch ($tipo) {
            case 'entrada':
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $productoMovimiento->producto->price,
                    $movimiento->user_id
                );
                $this->updateStock($stockDestino, -$cantidadAnterior, null, $movimiento->user_id);
                break;

            case 'salida':
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidadAnterior, $productoMovimiento->producto->price, $movimiento->user_id);
                }
                break;

            case 'traslado':
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                }
                $stockDestino = $this->getStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id
                );
                $this->handleRevertTraslado($stockOrigen, $stockDestino, $cantidadAnterior, $movimiento->user_id);
                break;

            case 'preparacion':
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidadAnterior, $productoMovimiento->producto->price, $movimiento->user_id);
                    break;
                }
            default:
                Log::warning("Tipo de movimiento no reconocido para revertir: {$tipo}");
        }
    }

    /**
     * Aplicar el nuevo impacto en el stock y registrar en stock_movements.
     *
     * @param string $tipo
     * @param Movimiento $movimiento
     * @param MovimientoProducto $productoMovimiento
     * @param int $cantidadNueva
     * @param \App\Models\Product $producto
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private function applyNewImpact(string $tipo, Movimiento $movimiento, MovimientoProducto $productoMovimiento, int $cantidadNueva, $producto, int $userId): void
    {
        switch ($tipo) {
            case 'entrada':
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->updateStock($stockDestino, $cantidadNueva, $producto->price, $userId);

                // Registrar en stock_movements
                $this->logStockMovement($movimiento, $productoMovimiento, 'entrada', $cantidadNueva);
                break;

            case 'salida':
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, 'salida', -$cantidadNueva);
                }
                break;

            case 'traslado':
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                }
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->handleTraslado($stockOrigen, $stockDestino, $cantidadNueva, $producto->price, $userId);

                // Registrar en stock_movements
                $this->logStockMovement($movimiento, $productoMovimiento, 'traslado', -$cantidadNueva);
                $this->logStockMovement($movimiento, $productoMovimiento, 'traslado', $cantidadNueva, 'entrada');
                break;

            case 'preparacion':
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, 'preparacion', -$cantidadNueva);
                }
                break;

            default:
                throw new Exception('Tipo de movimiento no válido.');
        }
    }

    /**
     * Obtener el stock existente o lanzar una excepción si no se encuentra.
     *
     * @param int $productoId
     * @param int $warehouseId
     * @param int $fieldId
     * @return Stock
     * @throws Exception
     */
    private function getStock(int $productoId, int $warehouseId, int $fieldId): Stock
    {
        Log::info("Buscando stock para producto ID: $productoId, bodega ID: $warehouseId, campo ID: $fieldId");

        $stock = Stock::where([
            'product_id' => $productoId,
            'warehouse_id' => $warehouseId,
            'field_id' => $fieldId,
        ])->first();

        if (!$stock) {
            Log::error("Stock no encontrado para el producto ID: $productoId en bodega ID: $warehouseId.");
            throw new Exception("Stock no encontrado en la bodega especificada.");
        }

        return $stock;
    }

    /**
     * Obtener o crear el stock de destino.
     *
     * @param int $productoId
     * @param int $warehouseId
     * @param int $fieldId
     * @param float $precioCompra
     * @param int $userId
     * @return Stock
     * @throws Exception
     */
    private function getOrCreateStock(int $productoId, int $warehouseId, int $fieldId, float $precioCompra, int $userId): Stock
    {
        if (is_null($warehouseId)) {
            throw new Exception('La bodega de destino no puede ser nula.');
        }

        Log::info("Obteniendo o creando stock para producto ID: $productoId, bodega ID: $warehouseId, campo ID: $fieldId");

        return Stock::firstOrCreate([
            'product_id' => $productoId,
            'warehouse_id' => $warehouseId,
            'field_id' => $fieldId,
        ], [
            'quantity' => 0,
            'price' => $precioCompra,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Actualizar la cantidad de stock.
     *
     * @param Stock $stock
     * @param int $cantidadCambio
     * @param float|null $nuevoPrecio
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private function updateStock(Stock $stock, int $cantidadCambio, ?float $nuevoPrecio, int $userId): void
    {
        // Verificar que no se exceda el stock mínimo
        if ($stock->quantity + $cantidadCambio < 0) {
            Log::error("Stock insuficiente para realizar la operación. Producto ID: {$stock->product_id}, Bodega ID: {$stock->warehouse_id}");
            throw new Exception("Stock insuficiente para realizar la operación.");
        }

        // Actualizar la cantidad del stock
        $stock->quantity += $cantidadCambio;
        if ($nuevoPrecio !== null) {
            $stock->price = $nuevoPrecio;
        }
        $stock->updated_by = $userId;
        $stock->save();

        Log::info("Stock actualizado: Producto ID {$stock->product_id}, Bodega ID {$stock->warehouse_id}, Cambio: {$cantidadCambio}");
    }

    /**
     * Manejar la salida de stock.
     *
     * @param Stock $stockOrigen
     * @param int $cantidad
     * @param float $precioCompra
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private function handleSalida(Stock $stockOrigen, int $cantidad, float $precioCompra, int $userId): void
    {
        $this->updateStock($stockOrigen, -$cantidad, $precioCompra, $userId);
    }

    /**
     * Manejar el traslado entre bodegas.
     *
     * @param Stock|null $stockOrigen
     * @param Stock $stockDestino
     * @param int $cantidad
     * @param float $precioCompra
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private function handleTraslado(?Stock $stockOrigen, Stock $stockDestino, int $cantidad, float $precioCompra, int $userId): void
    {
        DB::transaction(function () use ($stockOrigen, $stockDestino, $cantidad, $precioCompra, $userId) {
            if ($stockOrigen && $stockOrigen->quantity >= $cantidad) {
                $this->updateStock($stockOrigen, -$cantidad, null, $userId); // Restar cantidad en origen
                $this->updateStock($stockDestino, $cantidad, $precioCompra, $userId); // Sumar cantidad en destino
            } else {
                Log::error("Stock insuficiente para traslado: Producto ID {$stockOrigen->product_id}, Bodega Origen ID {$stockOrigen->warehouse_id}");
                throw new Exception("Stock insuficiente para traslado.");
            }
        });
    }

    /**
     * Manejar la reversión del traslado entre bodegas.
     *
     * @param Stock|null $stockOrigen
     * @param Stock|null $stockDestino
     * @param int $cantidad
     * @param int $userId
     * @return void
     */
    private function handleRevertTraslado(?Stock $stockOrigen, ?Stock $stockDestino, int $cantidad, int $userId): void
    {
        DB::transaction(function () use ($stockOrigen, $stockDestino, $cantidad, $userId) {
            if ($stockOrigen) {
                $this->updateStock($stockOrigen, $cantidad, null, $userId); // Sumar cantidad en origen
            }
            if ($stockDestino) {
                $this->updateStock($stockDestino, -$cantidad, null, $userId); // Restar cantidad en destino
            }
        });
    }

    /**
     * Registrar el movimiento en stock_movements.
     *
     * @param Movimiento $movimiento
     * @param MovimientoProducto $productoMovimiento
     * @param string $tipoMovimiento
     * @param int $cantidadCambio
     * @param string|null $descripcionAdicional
     * @return void
     */
    private function logStockMovement(Movimiento $movimiento, MovimientoProducto $productoMovimiento, string $tipoMovimiento, int $cantidadCambio, string $descripcionAdicional = null): void
    {
        $descripcion = $descripcionAdicional
            ? $descripcionAdicional
            : "Movimiento de tipo {$tipoMovimiento} registrado.";

        StockMovement::create([
            'movement_type' => $tipoMovimiento,
            'product_id' => $productoMovimiento->producto_id,
            'warehouse_id' => $tipoMovimiento === 'salida' ? $movimiento->bodega_origen_id : $movimiento->bodega_destino_id,
            'related_id' => $productoMovimiento->id,
            'related_type' => MovimientoProducto::class,
            'quantity_change' => $cantidadCambio,
            'description' => $descripcion,
            'user_id' => $movimiento->user_id,
            'field_id' => $movimiento->field_id,
            'updated_by' => $movimiento->updated_by ?? $movimiento->user_id,
        ]);

        Log::info("Movimiento registrado en stock_movements: Tipo {$tipoMovimiento}, Producto ID {$productoMovimiento->producto_id}, Cantidad Cambio {$cantidadCambio}");
    }

    /**
     * Revertir el impacto de un movimiento de producto.
     *
     * @param MovimientoProducto $productoMovimiento
     * @return void
     * @throws Exception
     */
    /**
     * Revertir el impacto de un MovimientoProducto en el stock.
     *
     * @param MovimientoProducto $productoMovimiento
     * @return void
     * @throws Exception
     */
    public function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        Log::info("StockService: Revirtiendo impacto para MovimientoProducto ID: {$productoMovimiento->id}");

        $movimiento = $productoMovimiento->movimiento;
        $tipo = strtolower($movimiento->tipo->value);
        $cantidad = $productoMovimiento->cantidad;
        $userId = Auth::id() ?? 1; // Asume un usuario por defecto si no está autenticado

        try {
            switch ($tipo) {
                case 'entrada':
                    // Para una entrada, restamos la cantidad al stock de destino
                    $stockDestino = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_destino_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockDestino, -$cantidad, null, $userId);

                    // Opcional: Eliminar los StockMovements asociados (ya se maneja en el Observer)
                    break;

                case 'salida':
                    // Para una salida, sumamos la cantidad al stock de origen
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidad, null, $userId);
                    break;

                case 'traslado':
                    // Para un traslado, sumamos al stock de origen y restamos del destino
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $stockDestino = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_destino_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidad, null, $userId);
                    $this->updateStock($stockDestino, -$cantidad, null, $userId);
                    break;

                default:
                    Log::warning("StockService: Tipo de movimiento no reconocido para revertir: {$tipo}");
            }

            Log::info("StockService: Impacto revertido correctamente para MovimientoProducto ID: {$productoMovimiento->id}");
        } catch (Exception $e) {
            Log::error("StockService Error: {$e->getMessage()} al revertir MovimientoProducto ID: {$productoMovimiento->id}");
            throw $e;
        }
    }

    /**
     * Validar que haya stock suficiente antes de crear un movimiento.
     *
     * @param MovimientoProducto $productoMovimiento
     * @return void
     * @throws Exception
     */
    public function validateStockBeforeCreating(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = strtolower($movimiento->tipo->value);
        $cantidad = $productoMovimiento->cantidad;

        if (in_array($tipo, ['salida', 'preparacion'])) {
            $stockOrigen = $this->getStock(
                $productoMovimiento->producto_id,
                $movimiento->bodega_origen_id,
                $movimiento->field_id
            );

            if ($stockOrigen->quantity < $cantidad) {
                Log::error("Stock insuficiente para {$tipo}: Producto ID {$productoMovimiento->producto_id}, Bodega Origen ID {$movimiento->bodega_origen_id}");
                throw new Exception("Stock insuficiente para {$tipo}.");
            }
        }
    }


    /**
     * Deduce stock basado en el uso de una aplicación.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @return void
     * @throws Exception
     */
    /*public function deductUsageStock(int $productId, int $warehouseId, float $quantity): void
    {
        DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $stock = Stock::where([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ])->lockForUpdate()->first();

            if (!$stock) {
                Log::error("Stock no encontrado para el producto ID: {$productId} en la bodega ID: {$warehouseId}");
                throw new Exception("Stock no disponible para el producto ID: {$productId} en la bodega ID: {$warehouseId}");
            }

            if ($stock->quantity < $quantity) {
                Log::error("Stock insuficiente para el producto ID: {$productId} en la bodega ID: {$warehouseId}. Disponible: {$stock->quantity}, Requerido: {$quantity}");
                throw new Exception("Stock insuficiente para el producto ID: {$productId} en la bodega ID: {$warehouseId}");
            }

            $stock->quantity -= $quantity;
            $stock->save();

            Log::info("Stock descontado por uso: Producto ID {$productId}, Bodega ID {$warehouseId}, Cantidad {$quantity}");
        });
    }

    /**
     * Revertir stock basado en el uso de una aplicación.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @return void
     */
    /*public function revertUsageStock(int $productId, int $warehouseId, float $quantity): void
   {
       DB::transaction(function () use ($productId, $warehouseId, $quantity) {
           $stock = Stock::where([
               'product_id' => $productId,
               'warehouse_id' => $warehouseId,
           ])->first();

           if (!$stock) {
               // Si el stock no existe, lo creamos
               $stock = Stock::create([
                   'product_id' => $productId,
                   'warehouse_id' => $warehouseId,
                   'quantity' => 0,
                   'price' => 0, // Ajustar según necesidades
                   'total_price' => 0, // Ajustar según necesidades
                   'field_id' => Filament::getTenant()->id,
                   'created_by' => Auth::id(),
                   'updated_by' => Auth::id(),
               ]);
           }

           $stock->quantity += $quantity;
           $stock->save();

           Log::info("Stock revertido por uso: Producto ID {$productId}, Bodega ID {$warehouseId}, Cantidad {$quantity}");
       });
   }
   /**
    * Registrar un movimiento de uso de aplicación en stock_movements.
    *
    * @param OrderApplicationUsage $usage
    * @param string $tipoMovimiento
    * @param int $cantidadCambio
    * @param string $descripcion
    * @return void
    */
    /*public function logUsageMovement(OrderApplicationUsage $usage, string $tipoMovimiento, int $cantidadCambio, string $descripcion): void
   {
       StockMovement::create([
           'movement_type' => $tipoMovimiento,
           'product_id' => $usage->product_id,
           'warehouse_id' => $usage->order->warehouse_id,
           'related_id' => $usage->id,
           'related_type' => OrderApplicationUsage::class,
           'quantity_change' => $cantidadCambio,
           'description' => $descripcion,
           'user_id' => $usage->user_id,
           'field_id' => $usage->field_id,
           'updated_by' => $usage->updated_by ?? $usage->user_id,
       ]);

       Log::info("Movimiento registrado en stock_movements: Tipo {$tipoMovimiento}, Producto ID {$usage->product_id}, Cantidad Cambio {$cantidadCambio}");
   }*/
}
