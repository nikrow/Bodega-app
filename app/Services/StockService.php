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
use App\Models\StockHistory; // <-- Modelo del historial de stock
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

        // Verificar que exista el producto
        $producto = $productoMovimiento->producto;
        if (!$producto) {
            Log::error('Producto no encontrado para el MovimientoProducto ID: ' . $productoMovimiento->id);
            throw new ProductNotFoundException('Producto no encontrado.');
        }

        // Revertir el impacto anterior de stock si hay cantidad anterior
        if ($cantidadAnterior !== null) {
            $this->revertPreviousImpact($tipo, $movimiento, $productoMovimiento, $cantidadAnterior);
        }

        // Aplicar el nuevo impacto de stock
        $this->applyNewImpact($tipo, $movimiento, $productoMovimiento, $cantidadNueva, $producto, $userId);
    }

    /**
     * Revertir el impacto anterior en el stock.
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
                $this->updateStock($stockDestino, -$cantidadAnterior, null, $movimiento->user_id, $movimiento, $productoMovimiento);

                break;

            case MovementType::PREPARACION:
            case MovementType::SALIDA:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock(
                        $stockOrigen,
                        $cantidadAnterior,
                        $productoMovimiento->producto->price,
                        $movimiento->user_id,
                        $movimiento,
                        $productoMovimiento
                    );
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
                $this->handleRevertTraslado($stockOrigen, $stockDestino, $cantidadAnterior, $movimiento->user_id, $movimiento, $productoMovimiento);
                break;

            default:
                Log::warning("Tipo de movimiento no reconocido para revertir: {$tipo->value}");
                throw new InvalidMovementTypeException("Tipo de movimiento no válido: {$tipo->value}");
        }
    }

    /**
     * Aplicar el nuevo impacto de stock y registrar en stock_movements.
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
                $this->updateStock($stockDestino, $cantidadNueva, $producto->price, $userId, $movimiento, $productoMovimiento);

                // Registrar en stock_movements (sin signos negativos)
                $descripcion = "Entrada: OC: {$movimiento->orden_compra}, GD: {$movimiento->guia_despacho},
                                Proveedor: {$movimiento->nombre_proveedor}. ID Movimiento: {$movimiento->id}";
                $this->logStockMovement($movimiento, $productoMovimiento, $tipo, $cantidadNueva, $descripcion, $movimiento->bodega_destino_id);
                break;

            case MovementType::SALIDA:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId, $movimiento, $productoMovimiento);

                    // Registrar en stock_movements (sin signos negativos)
                    $descripcion = "Salida: GD: {$movimiento->guia_despacho}. ID Movimiento: {$movimiento->id}";
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, $cantidadNueva, $descripcion, $movimiento->bodega_origen_id);
                }
                break;

            case MovementType::TRASLADO:
                // Si hay bodega origen
                $stockOrigen = null;
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                }
                // Bodega destino
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->handleTraslado($stockOrigen, $stockDestino, $cantidadNueva, $producto->price, $userId, $movimiento, $productoMovimiento);

                // En lugar de duplicar "TRASLADO", creamos TRASLADO-SALIDA y TRASLADO-ENTRADA
                $descripcionSalida  = "Traslado - Salida. ID Movimiento: {$movimiento->id}";
                $descripcionEntrada = "Traslado - Entrada. ID Movimiento: {$movimiento->id}";

                $this->logStockMovement($movimiento, $productoMovimiento, 'TRASLADO-SALIDA', $cantidadNueva, $descripcionSalida, $movimiento->bodega_origen_id);
                $this->logStockMovement($movimiento, $productoMovimiento, 'TRASLADO-ENTRADA', $cantidadNueva, $descripcionEntrada, $movimiento->bodega_destino_id);
                break;

            case MovementType::PREPARACION:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );

                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId, $movimiento, $productoMovimiento);

                    $descripcion = "Preparacion. ID Movimiento: {$movimiento->id}";
                    $this->logStockMovement($movimiento, $productoMovimiento, $tipo, $cantidadNueva, $descripcion, $movimiento->bodega_origen_id);
                }
                break;

            default:
                Log::warning("Tipo de movimiento no reconocido para aplicar: {$tipo->value}");
                throw new InvalidMovementTypeException('Tipo de movimiento no válido.');
        }
    }

    /**
     * Obtener el stock existente o lanzar una excepción si no se encuentra.
     */
    private function getStock(int $productoId, int $warehouseId, int $fieldId): Stock
    {
        Log::info("Buscando stock para producto ID: {$productoId}, bodega ID: {$warehouseId}, campo ID: {$fieldId}");

        $stock = Stock::where([
            'product_id'   => $productoId,
            'warehouse_id' => $warehouseId,
            'field_id'     => $fieldId,
        ])->first();

        if (!$stock) {
            Log::error("Stock no encontrado para el producto ID: {$productoId} en bodega ID: {$warehouseId}.");
            throw new WarehouseNotFoundException("Stock no encontrado en la bodega especificada (ID: {$warehouseId}).");
        }

        return $stock;
    }

    /**
     * Obtener o crear el stock de destino.
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
                'product_id'   => $productoId,
                'warehouse_id' => $warehouseId,
                'field_id'     => $fieldId,
            ],
            [
                'quantity'   => 0,
                'price'      => $precioCompra,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    /**
     * Actualizar la cantidad de stock.
     */
    private function updateStock(Stock $stock, float $cantidadCambio, ?float $nuevoPrecio, int $userId, Movimiento $movimiento, MovimientoProducto $productoMovimiento): void
    {
        DB::transaction(function () use ($stock, $cantidadCambio, $nuevoPrecio, $userId, $movimiento,$productoMovimiento) {
            if ($stock->quantity + $cantidadCambio < 0) {
                Log::error("Stock insuficiente para realizar la operación. Producto ID: {$stock->product_id}, Bodega ID: {$stock->warehouse_id}");
                throw new InsufficientStockException("Stock insuficiente para realizar la operación.");
            }

            // Actualizar la cantidad
            $stock->quantity += $cantidadCambio;

            // Actualizar precio si corresponde
            if ($nuevoPrecio !== null) {
                $stock->price = $nuevoPrecio;
            }
            $stock->updated_by = $userId;
            $stock->save();

            Log::info("Stock actualizado: Producto ID {$stock->product_id}, Bodega ID {$stock->warehouse_id}, Cambio: {$cantidadCambio}");

            // Tras la actualización, registramos snapshot de StockHistory
            $this->createStockHistory($stock, $cantidadCambio, $userId, $movimiento, $productoMovimiento);

        });
    }

    /**
     * Manejar la salida de stock.
     */
    private function handleSalida(Stock $stockOrigen, float $cantidad, float $precioCompra, int $userId, Movimiento $movimiento, MovimientoProducto $productoMovimiento): void
    {
        $this->updateStock($stockOrigen, -$cantidad, $precioCompra, $userId, $movimiento,$productoMovimiento);
    }

    /**
     * Manejar el traslado entre bodegas.
     */
    private function handleTraslado(?Stock $stockOrigen, Stock $stockDestino, float $cantidad, float $precioCompra, int $userId, Movimiento $movimiento, MovimientoProducto $productoMovimiento): void
    {
        DB::transaction(function () use ($stockOrigen, $stockDestino, $cantidad, $precioCompra, $userId, $movimiento, $productoMovimiento) {
            if ($stockOrigen && $stockOrigen->quantity >= $cantidad) {
                // Restar en origen
                $this->updateStock($stockOrigen, -$cantidad, null, $userId, $movimiento,$productoMovimiento);
                // Sumar en destino
                $this->updateStock($stockDestino, $cantidad, $precioCompra, $userId, $movimiento,$productoMovimiento);
            } else {
                Log::error("Stock insuficiente para traslado: Producto ID {$stockOrigen?->product_id}, Bodega Origen ID {$stockOrigen?->warehouse_id}");
                throw new InsufficientStockException("Stock insuficiente para traslado.");
            }
        });
    }

    /**
     * Manejar la reversión del traslado entre bodegas.
     */
    private function handleRevertTraslado(?Stock $stockOrigen, ?Stock $stockDestino, float $cantidad, int $userId, Movimiento $movimiento, MovimientoProducto $productoMovimiento): void
    {
        DB::transaction(function () use ($stockOrigen, $stockDestino, $cantidad, $userId, $movimiento, $productoMovimiento) {
            if ($stockOrigen) {
                $this->updateStock($stockOrigen, $cantidad, null, $userId, $movimiento, $productoMovimiento);
            }
            if ($stockDestino) {
                $this->updateStock($stockDestino, -$cantidad, null, $userId, $movimiento, $productoMovimiento);
            }
        });
    }
    private function logStockMovement(
        Movimiento $movimiento,
        MovimientoProducto $productoMovimiento,
        string|MovementType $tipoMovimiento,
        float $cantidadCambio,
        ?string $descripcionAdicional = null,
        ?int $warehouseId = null
    ): void {
        // Convertir $tipoMovimiento a string, sea un enum o un string
        $tipoString = is_string($tipoMovimiento)
            ? $tipoMovimiento
            : $tipoMovimiento->value; // extrae la propiedad 'value' del enum

        if ($warehouseId === null) {
            $warehouseId = match ($tipoMovimiento) {
                MovementType::SALIDA, MovementType::PREPARACION, 'traslado-salida' => $movimiento->bodega_origen_id,
                MovementType::ENTRADA, 'traslado-entrada' => $movimiento->bodega_destino_id,
                default => null,
            };
        }

        $descripcion = $descripcionAdicional
            ? $descripcionAdicional
            : "Movimiento {$productoMovimiento->id} de tipo {$tipoString} registrado.";

        // Registrar en stock_movements
        StockMovement::create([
            'movement_type'   => $tipoString,   // ya es un string
            'product_id'      => $productoMovimiento->producto_id,
            'warehouse_id'    => $warehouseId,
            'related_id'      => $productoMovimiento->id,
            'related_type'    => MovimientoProducto::class,
            'quantity_change' => $cantidadCambio,
            'description'     => $descripcion,
            'user_id'         => $movimiento->user_id,
            'field_id'        => $movimiento->field_id,
            'updated_by'      => $movimiento->updated_by ?? $movimiento->user_id,
        ]);

        Log::info("Movimiento registrado: Tipo {$tipoString}, Prod ID {$productoMovimiento->producto_id},
               Cantidad {$cantidadCambio}, Bodega {$warehouseId}, Desc: {$descripcion}");
    }


    /**
     * Crear un snapshot en StockHistory justo después de cada updateStock.
     */
    private function createStockHistory(Stock $stock, float $cantidadCambio, int $userId, Movimiento $movimiento,MovimientoProducto $productoMovimiento): void
    {
        StockHistory::create([
            'stock_id'          => $stock->id,
            'product_id'        => $stock->product_id,
            'movement_id'       => $movimiento->id,
            'movement_product_id' => $productoMovimiento->id,
            'warehouse_id'      => $stock->warehouse_id,
            'field_id'          => $stock->field_id,
            'quantity_snapshot' => $stock->quantity,   // Cantidad final tras update
            'price_snapshot'    => $stock->price,      // Precio final tras update
            'created_by'        => $userId,
        ]);
    }

    /**
     * Revertir el impacto de un movimiento de producto.
     */
    public function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        Log::info("StockService: Revirtiendo impacto (SIN HISTORY) para MovimientoProducto ID: {$productoMovimiento->id}");

        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidad = $productoMovimiento->cantidad;
        $userId = Auth::id() ?? 1;

        try {
            switch ($tipo) {
                case MovementType::ENTRADA:
                    // Revertir entrada: restamos en destino SIN crear history
                    $stockDestino = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_destino_id,
                        $movimiento->field_id
                    );
                    $this->updateStockWithoutHistory($stockDestino, -$cantidad, null, $userId);
                    break;

                case MovementType::SALIDA:
                    // Revertir salida: sumamos en origen SIN crear history
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStockWithoutHistory($stockOrigen, $cantidad, null, $userId);
                    break;

                case MovementType::TRASLADO:
                    // Revertir traslado: sumamos en origen y restamos en destino SIN history
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

                    $this->updateStockWithoutHistory($stockOrigen, $cantidad, null, $userId);
                    $this->updateStockWithoutHistory($stockDestino, -$cantidad, null, $userId);
                    break;

                case MovementType::PREPARACION:
                    // Revertir preparación: sumamos al origen SIN history
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStockWithoutHistory($stockOrigen, $cantidad, null, $userId);
                    break;

                default:
                    Log::warning("StockService: Tipo de movimiento no reconocido para revertir: {$tipo->value}");
                    throw new InvalidMovementTypeException("Tipo de movimiento no válido: {$tipo->value}");
            }

            Log::info("StockService: Impacto revertido SIN HISTORY para MovimientoProducto ID: {$productoMovimiento->id}");
        } catch (Exception $e) {
            Log::error("StockService Error: {$e->getMessage()} al revertir SIN HISTORY - MovimientoProducto ID: {$productoMovimiento->id}");
            throw $e;
        }
    }

    /**
     * Validar stock suficiente antes de crear un movimiento (salida, preparación).
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
                Log::error("Stock insuficiente para {$tipo->value}: Prod ID {$productoMovimiento->producto_id}, Bodega Origen ID {$movimiento->bodega_origen_id}");
                throw new InsufficientStockException("Stock insuficiente para {$tipo->value}.");
            }
        }
    }
    /**
     * Actualizar la cantidad de stock SIN crear un snapshot en StockHistory.
     */
    private function updateStockWithoutHistory(
        Stock $stock,
        float $cantidadCambio,
        ?float $nuevoPrecio,
        int $userId
    ): void {
        DB::transaction(function () use ($stock, $cantidadCambio, $nuevoPrecio, $userId) {
            if ($stock->quantity + $cantidadCambio < 0) {
                Log::error("Stock insuficiente para la operación. Producto ID: {$stock->product_id}, Bodega ID: {$stock->warehouse_id}");
                throw new InsufficientStockException("Stock insuficiente para la operación.");
            }

            // Actualizar la cantidad
            $stock->quantity += $cantidadCambio;

            // Actualizar precio si corresponde
            if ($nuevoPrecio !== null) {
                $stock->price = $nuevoPrecio;
            }
            $stock->updated_by = $userId;
            $stock->save();

            Log::info("Stock (SIN HISTORY) actualizado: Producto ID {$stock->product_id}, Bodega ID {$stock->warehouse_id}, Cambio: {$cantidadCambio}");
        });
    }
}
