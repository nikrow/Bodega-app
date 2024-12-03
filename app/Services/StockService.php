<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\InvalidMovementTypeException;
use App\Exceptions\Stock\ProductNotFoundException;
use App\Exceptions\Stock\WarehouseNotFoundException;
use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Models\Stock;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Aplicar cambios de stock basados en un movimiento de producto.
     *
     * @param MovimientoProducto $productoMovimiento
     * @param float|null $cantidadAnterior
     * @return void
     * @throws Exception
     */
    public function applyStockChanges(MovimientoProducto $productoMovimiento, ?float $cantidadAnterior = null): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidadNueva = $productoMovimiento->cantidad;

        $userId = Auth::id();

        // Obtener el producto asociado al movimiento
        $producto = $productoMovimiento->producto;
        if (!$producto) {
            Log::error('Producto no encontrado para el MovimientoProducto ID: ' . $productoMovimiento->id);
            throw new ProductNotFoundException('Producto no encontrado.');
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
     * @param MovementType $tipo
     * @param Movimiento $movimiento
     * @param MovimientoProducto $productoMovimiento
     * @param float $cantidadAnterior
     * @return void
     * @throws Exception
     */
    private function revertPreviousImpact(MovementType $tipo, Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidadAnterior): void
    {
        switch ($tipo) {
            case MovementType::ENTRADA:
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $productoMovimiento->producto->price,
                    $movimiento->user_id
                );
                $this->updateStock($stockDestino, -$cantidadAnterior, null, $movimiento->user_id);
                break;

            case MovementType::SALIDA:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidadAnterior, $productoMovimiento->producto->price, $movimiento->user_id);
                }
                break;

            case MovementType::TRASLADO:
                $stockOrigen = null;
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

            case MovementType::PREPARACION:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidadAnterior, $productoMovimiento->producto->price, $movimiento->user_id);
                }
                break;

            default:
                Log::warning("Tipo de movimiento no reconocido para revertir: {$tipo->value}");
                throw new InvalidMovementTypeException("Tipo de movimiento no válido: {$tipo->value}");
        }
    }

    /**
     * Aplicar el nuevo impacto en el stock y registrar en stock_movements.
     *
     * @param MovementType $tipo
     * @param Movimiento $movimiento
     * @param MovimientoProducto $productoMovimiento
     * @param float $cantidadNueva
     * @param \App\Models\Product $producto
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private function applyNewImpact(MovementType $tipo, Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidadNueva, $producto, int $userId): void
    {
        switch ($tipo) {
            case MovementType::ENTRADA:
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->updateStock($stockDestino, $cantidadNueva, $producto->price, $userId);

                // Registrar en stock_movements
                $this->logStockMovement($movimiento, $productoMovimiento, $tipo, $cantidadNueva, 'entrada');
                break;

            case MovementType::SALIDA:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, -$cantidadNueva, 'salida');
                }
                break;

            case MovementType::TRASLADO:
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

                // Registrar en stock_movements para salida y entrada
                $this->logStockMovement($movimiento, $productoMovimiento, MovementType::TRASLADO, -$cantidadNueva, 'traslado - salida');
                $this->logStockMovement($movimiento, $productoMovimiento, MovementType::TRASLADO, $cantidadNueva, 'traslado - entrada');
                break;

            case MovementType::PREPARACION:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, -$cantidadNueva, 'preparacion - salida');
                }
                break;

            default:
                Log::warning("Tipo de movimiento no reconocido para aplicar: {$tipo->value}");
                throw new InvalidMovementTypeException('Tipo de movimiento no válido.');
        }
    }

    /**
     * Obtener el stock existente o lanzar una excepción si no se encuentra.
     *
     * @param int $productoId
     * @param int $warehouseId
     * @param int $fieldId
     * @return Stock
     * @throws WarehouseNotFoundException
     */
    private function getStock(int $productoId, int $warehouseId, int $fieldId): Stock
    {
        Log::info("Buscando stock para producto ID: {$productoId}, bodega ID: {$warehouseId}, campo ID: {$fieldId}");

        $stock = Stock::where([
            'product_id' => $productoId,
            'warehouse_id' => $warehouseId,
            'field_id' => $fieldId,
        ])->first();

        if (!$stock) {
            Log::error("Stock no encontrado para el producto ID: {$productoId} en bodega ID: {$warehouseId}.");
            throw new WarehouseNotFoundException("Stock no encontrado en la bodega especificada (ID: {$warehouseId}).");
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
     * @throws WarehouseNotFoundException
     */
    private function getOrCreateStock(int $productoId, int $warehouseId, int $fieldId, float $precioCompra, int $userId): Stock
    {
        if (is_null($warehouseId)) {
            Log::error('La bodega de destino no puede ser nula.');
            throw new WarehouseNotFoundException('La bodega de destino no puede ser nula.');
        }

        Log::info("Obteniendo o creando stock para producto ID: {$productoId}, bodega ID: {$warehouseId}, campo ID: {$fieldId}");

        return Stock::firstOrCreate(
            [
                'product_id' => $productoId,
                'warehouse_id' => $warehouseId,
                'field_id' => $fieldId,
            ],
            [
                'quantity' => 0,
                'price' => $precioCompra,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    /**
     * Actualizar la cantidad de stock.
     *
     * @param Stock $stock
     * @param float $cantidadCambio
     * @param float|null $nuevoPrecio
     * @param int $userId
     * @return void
     * @throws InsufficientStockException
     */
    private function updateStock(Stock $stock, float $cantidadCambio, ?float $nuevoPrecio, int $userId): void
    {
        DB::transaction(function () use ($stock, $cantidadCambio, $nuevoPrecio, $userId) {
            // Verificar que no se exceda el stock mínimo
            if ($stock->quantity + $cantidadCambio < 0) {
                Log::error("Stock insuficiente para realizar la operación. Producto ID: {$stock->product_id}, Bodega ID: {$stock->warehouse_id}");
                throw new InsufficientStockException("Stock insuficiente para realizar la operación.");
            }

            // Actualizar la cantidad del stock
            $stock->quantity += $cantidadCambio;
            if ($nuevoPrecio !== null) {
                $stock->price = $nuevoPrecio;
            }
            $stock->updated_by = $userId;
            $stock->save();

            Log::info("Stock actualizado: Producto ID {$stock->product_id}, Bodega ID {$stock->warehouse_id}, Cambio: {$cantidadCambio}");
        });
    }

    /**
     * Manejar la salida de stock.
     *
     * @param Stock $stockOrigen
     * @param float $cantidad
     * @param float $precioCompra
     * @param int $userId
     * @return void
     * @throws InsufficientStockException
     */
    private function handleSalida(Stock $stockOrigen, float $cantidad, float $precioCompra, int $userId): void
    {
        $this->updateStock($stockOrigen, -$cantidad, $precioCompra, $userId);
    }

    /**
     * Manejar el traslado entre bodegas.
     *
     * @param Stock|null $stockOrigen
     * @param Stock $stockDestino
     * @param float $cantidad
     * @param float $precioCompra
     * @param int $userId
     * @return void
     * @throws InsufficientStockException
     */
    private function handleTraslado(?Stock $stockOrigen, Stock $stockDestino, float $cantidad, float $precioCompra, int $userId): void
    {
        DB::transaction(function () use ($stockOrigen, $stockDestino, $cantidad, $precioCompra, $userId) {
            if ($stockOrigen && $stockOrigen->quantity >= $cantidad) {
                $this->updateStock($stockOrigen, -$cantidad, null, $userId); // Restar cantidad en origen
                $this->updateStock($stockDestino, $cantidad, $precioCompra, $userId); // Sumar cantidad en destino
            } else {
                Log::error("Stock insuficiente para traslado: Producto ID {$stockOrigen->product_id}, Bodega Origen ID {$stockOrigen->warehouse_id}");
                throw new InsufficientStockException("Stock insuficiente para traslado.");
            }
        });
    }

    /**
     * Manejar la reversión del traslado entre bodegas.
     *
     * @param Stock|null $stockOrigen
     * @param Stock|null $stockDestino
     * @param float $cantidad
     * @param int $userId
     * @return void
     */
    private function handleRevertTraslado(?Stock $stockOrigen, ?Stock $stockDestino, float $cantidad, int $userId): void
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
     * @param MovementType $tipoMovimiento
     * @param float $cantidadCambio
     * @param string|null $descripcionAdicional
     * @return void
     */
    private function logStockMovement(Movimiento $movimiento, MovimientoProducto $productoMovimiento, MovementType $tipoMovimiento, float $cantidadCambio, ?string $descripcionAdicional = null): void
    {
        $descripcion = $descripcionAdicional
            ? $descripcionAdicional
            : "Movimiento {$productoMovimiento->id} de tipo {$tipoMovimiento->value} registrado.";

        // Determinar la bodega relevante basada en el tipo de movimiento
        $warehouseId = match ($tipoMovimiento) {
            MovementType::SALIDA, MovementType::PREPARACION, MovementType::TRASLADO => $movimiento->bodega_origen_id,
            MovementType::ENTRADA => $movimiento->bodega_destino_id,
        };

        StockMovement::create([
            'movement_type' => $tipoMovimiento->value,
            'product_id' => $productoMovimiento->producto_id,
            'warehouse_id' => $warehouseId,
            'related_id' => $productoMovimiento->id,
            'related_type' => MovimientoProducto::class,
            'quantity_change' => $cantidadCambio,
            'description' => $descripcion,
            'user_id' => $movimiento->user_id,
            'field_id' => $movimiento->field_id,
            'updated_by' => $movimiento->updated_by ?? $movimiento->user_id,
        ]);

        Log::info("Movimiento registrado en stock_movements: Tipo {$tipoMovimiento->value}, Producto ID {$productoMovimiento->producto_id}, Cantidad Cambio {$cantidadCambio}, Bodega ID {$warehouseId}");
    }

    /**
     * Revertir el impacto de un movimiento de producto.
     *
     * @param MovimientoProducto $productoMovimiento
     * @return void
     * @throws Exception
     */
    public function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        Log::info("StockService: Revirtiendo impacto para MovimientoProducto ID: {$productoMovimiento->id}");

        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidad = $productoMovimiento->cantidad;
        $userId = Auth::id() ?? 1; // Asume un usuario por defecto si no está autenticado

        try {
            switch ($tipo) {
                case MovementType::ENTRADA:
                    // Para una entrada, restamos la cantidad al stock de destino
                    $stockDestino = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_destino_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockDestino, -$cantidad, null, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, -$cantidad, 'reversión entrada');
                    break;

                case MovementType::SALIDA:
                    // Para una salida, sumamos la cantidad al stock de origen
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidad, null, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, $cantidad, 'reversión salida');
                    break;

                case MovementType::TRASLADO:
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

                    // Registrar en stock_movements para reversión de traslado
                    $this->logStockMovement($movimiento, $productoMovimiento, MovementType::TRASLADO, $cantidad, 'reversión traslado - salida');
                    $this->logStockMovement($movimiento, $productoMovimiento, MovementType::TRASLADO, -$cantidad, 'reversión traslado - entrada');
                    break;

                case MovementType::PREPARACION:
                    // Para una preparación, sumamos la cantidad al stock de origen
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidad, null, $userId);

                    // Registrar en stock_movements
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, $cantidad, 'reversión preparación - salida');
                    break;

                default:
                    Log::warning("StockService: Tipo de movimiento no reconocido para revertir: {$tipo->value}");
                    throw new InvalidMovementTypeException("Tipo de movimiento no válido: {$tipo->value}");
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
     * @throws InsufficientStockException
     */
    public function validateStockBeforeCreating(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidad = $productoMovimiento->cantidad;

        if (in_array($tipo, [MovementType::SALIDA, MovementType::PREPARACION])) {
            $stockOrigen = $this->getStock(
                $productoMovimiento->producto_id,
                $movimiento->bodega_origen_id,
                $movimiento->field_id
            );

            if ($stockOrigen->quantity < $cantidad) {
                Log::error("Stock insuficiente para {$tipo->value}: Producto ID {$productoMovimiento->producto_id}, Bodega Origen ID {$movimiento->bodega_origen_id}");
                throw new InsufficientStockException("Stock insuficiente para {$tipo->value}.");
            }
        }
    }

}
