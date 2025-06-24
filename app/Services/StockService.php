<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Batch;
use App\Models\Stock;
use App\Models\Movimiento;
use App\Models\StockMovement;
use App\Models\MovimientoProducto;
use App\Models\InterTenantTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Stock\ProductNotFoundException;
use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\WarehouseNotFoundException;
use App\Exceptions\Stock\InvalidMovementTypeException;

class StockService
{
    /**
     * Aplica los cambios de stock para un movimiento de producto.
     */
    public function applyStockChanges(MovimientoProducto $productoMovimiento, ?float $cantidadAnterior = null): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidadNueva = $productoMovimiento->cantidad;
        $userId = Auth::id() ?? 1;

        $producto = $productoMovimiento->producto;
        if (!$producto) {
            Log::error("Producto no encontrado para MovimientoProducto ID: {$productoMovimiento->id}");
            throw new ProductNotFoundException('Producto no encontrado.');
        }

        DB::transaction(function () use ($tipo, $movimiento, $productoMovimiento, $cantidadNueva, $cantidadAnterior, $producto, $userId) {
            if ($cantidadAnterior !== null) {
                $this->revertPreviousImpact($tipo, $movimiento, $productoMovimiento, $cantidadAnterior);
            }

            $this->handleMovement($tipo, $movimiento, $productoMovimiento, $cantidadNueva, $producto, $userId);
        });
    }

    /**
     * Maneja el impacto de un movimiento según su tipo.
     */
    private function handleMovement(MovementType $tipo, Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidad, $producto, int $userId): void
    {
        switch ($tipo) {
            case MovementType::ENTRADA:
                $this->handleEntrada($movimiento, $productoMovimiento, $cantidad, $producto, $userId);
                break;

            case MovementType::SALIDA:
                $this->handleSalida($movimiento, $productoMovimiento, $cantidad, $producto, $userId);
                break;

            case MovementType::TRASLADO:
                $this->handleTraslado($movimiento, $productoMovimiento, $cantidad, $producto, $userId);
                break;

            case MovementType::PREPARACION:
                $this->handlePreparacion($movimiento, $productoMovimiento, $cantidad, $producto, $userId);
                break;

            case MovementType::TRASLADO_CAMPOS:
                $this->handleTrasladoCampos($movimiento, $productoMovimiento, $cantidad, $producto, $userId);
                break;

            default:
                Log::warning("Tipo de movimiento no reconocido: {$tipo->value}");
                throw new InvalidMovementTypeException("Tipo de movimiento no válido: {$tipo->value}");
        }
    }

    /**
     * Maneja un movimiento de tipo ENTRADA.
     */
    private function handleEntrada(Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidad, $producto, int $userId): void
    {
        if ($producto->requiresBatchControl()) {
            $this->createBatch($productoMovimiento, $cantidad, $movimiento);
        }

        $stockDestino = $this->getOrCreateStock(
            $productoMovimiento->producto_id,
            $movimiento->bodega_destino_id,
            $movimiento->field_id,
            $producto->price,
            $userId
        );

        $this->updateStock($stockDestino, $cantidad, $producto->price, $userId, $movimiento, $productoMovimiento);

        $descripcion = "OC: {$movimiento->orden_compra}, GD: {$movimiento->guia_despacho}, Proveedor: {$movimiento->nombre_proveedor}";
        if ($movimiento->interTenantTransfer) {
            $descripcion .= " - Transferencia Inter-Tenant ID: {$movimiento->interTenantTransfer->id}";
        }

        $this->logStockMovement(
            $movimiento,
            $productoMovimiento,
            MovementType::ENTRADA,
            $cantidad,
            $descripcion,
            $movimiento->bodega_destino_id
        );
    }

    /**
     * Maneja un movimiento de tipo SALIDA.
     */
    private function handleSalida(Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidad, $producto, int $userId): void
    {
        if (!$movimiento->bodega_origen_id) {
            throw new WarehouseNotFoundException('Bodega de origen no especificada.');
        }

        $stockOrigen = $this->getStock(
            $productoMovimiento->producto_id,
            $movimiento->bodega_origen_id,
            $movimiento->field_id
        );

        $this->updateStock($stockOrigen, -$cantidad, $producto->price, $userId, $movimiento, $productoMovimiento);

        $descripcion = "Salida: GD: {$movimiento->guia_despacho}. ID Movimiento: {$movimiento->id}";
        if ($movimiento->interTenantTransfer) {
            $descripcion .= " - Transferencia Inter-Tenant ID: {$movimiento->interTenantTransfer->id}";
        }

        $this->logStockMovement(
            $movimiento,
            $productoMovimiento,
            MovementType::SALIDA,
            $cantidad,
            $descripcion,
            $movimiento->bodega_origen_id
        );
    }

    /**
     * Maneja un movimiento de tipo TRASLADO.
     */
    private function handleTraslado(Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidad, $producto, int $userId): void
    {
        $stockOrigen = $movimiento->bodega_origen_id
            ? $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id)
            : null;

        $stockDestino = $this->getOrCreateStock(
            $productoMovimiento->producto_id,
            $movimiento->bodega_destino_id,
            $movimiento->field_id,
            $producto->price,
            $userId
        );

        if ($stockOrigen) {
            $this->updateStock($stockOrigen, -$cantidad, null, $userId, $movimiento, $productoMovimiento);
        }
        $this->updateStock($stockDestino, $cantidad, $producto->price, $userId, $movimiento, $productoMovimiento);

        $this->logStockMovement(
            $movimiento,
            $productoMovimiento,
            'traslado-salida',
            $cantidad,
            "Traslado hacia " . optional($movimiento->bodega_destino)->name,
            $movimiento->bodega_origen_id
        );

        $this->logStockMovement(
            $movimiento,
            $productoMovimiento,
            'traslado-entrada',
            $cantidad,
            "Traslado desde " . optional($movimiento->bodega_origen)->name,
            $movimiento->bodega_destino_id
        );
    }

    /**
     * Maneja un movimiento de tipo PREPARACION.
     */
    private function handlePreparacion(Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidad, $producto, int $userId): void
    {
        if (!$movimiento->bodega_origen_id) {
            throw new WarehouseNotFoundException('Bodega de origen no especificada.');
        }

        $stockOrigen = $this->getStock(
            $productoMovimiento->producto_id,
            $movimiento->bodega_origen_id,
            $movimiento->field_id
        );

        $this->updateStock($stockOrigen, -$cantidad, $producto->price, $userId, $movimiento, $productoMovimiento);

        $this->logStockMovement(
            $movimiento,
            $productoMovimiento,
            MovementType::PREPARACION,
            $cantidad,
            "Preparación. ID Movimiento: {$movimiento->id}",
            $movimiento->bodega_origen_id
        );
    }

    /**
     * Maneja un movimiento de tipo TRASLADO_CAMPOS.
     */
    private function handleTrasladoCampos(Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidad, $producto, int $userId): void
    {
        if (!$movimiento->bodega_origen_id || !$movimiento->tenant_destino_id || !$movimiento->bodega_destino_id) {
            throw new \Exception('Faltan datos para el traslado entre campos.');
        }

        $stockOrigen = $this->getStock(
            $productoMovimiento->producto_id,
            $movimiento->bodega_origen_id,
            $movimiento->field_id
        );

        if ($stockOrigen->quantity < $cantidad) {
            throw new InsufficientStockException('Stock insuficiente para traslado entre campos.');
        }

        $this->updateStock($stockOrigen, -$cantidad, null, $userId, $movimiento, $productoMovimiento);

        $this->logStockMovement(
            $movimiento,
            $productoMovimiento,
            'traslado-campos-salida',
            $cantidad,
            "Traslado entre campos preparado para {$movimiento->tenantDestino->name}",
            $movimiento->bodega_origen_id
        );
    }

    /**
     * Aprueba una transferencia inter-tenant y actualiza el stock en el destino.
     */
    public function approveInterTenantTransfer(InterTenantTransfer $transfer, int $userId): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->estado !== 'pendiente') {
                Log::warning("Intento de aprobar transferencia ya procesada: ID {$transfer->id}");
                throw new \Exception('La transferencia ya ha sido procesada.');
            }

            $movimientoOrigen = $transfer->movimientoOrigen;
            $productoMovimientos = $movimientoOrigen->movimientoProductos()->where('inter_tenant_transfer_id', $transfer->id)->get();

            $movimientoDestino = Movimiento::create([
                'user_id' => $userId,
                'movement_number' => $this->generateUniqueMovementNumber(),
                'tipo' => MovementType::ENTRADA->value,
                'field_id' => $transfer->tenant_destino_id,
                'bodega_destino_id' => $transfer->bodega_destino_id,
                'inter_tenant_transfer_id' => $transfer->id,
                'created_by' => $userId,
                'updated_by' => $userId,
                'is_completed' => true,
            ]);

            foreach ($productoMovimientos as $productoMovimiento) {
                $newProductoMovimiento = MovimientoProducto::create([
                    'movimiento_id' => $movimientoDestino->id,
                    'producto_id' => $productoMovimiento->producto_id,
                    'cantidad' => $productoMovimiento->cantidad,
                    'lot_number' => $productoMovimiento->lot_number,
                    'expiration_date' => $productoMovimiento->expiration_date,
                    'inter_tenant_transfer_id' => $transfer->id,
                ]);

                $stockDestino = $this->getOrCreateStock(
                    $newProductoMovimiento->producto_id,
                    $movimientoDestino->bodega_destino_id,
                    $movimientoDestino->field_id,
                    $productoMovimiento->producto->price,
                    $userId
                );

                $this->updateStock(
                    $stockDestino,
                    $newProductoMovimiento->cantidad,
                    $productoMovimiento->producto->price,
                    $userId,
                    $movimientoDestino,
                    $newProductoMovimiento
                );

                $this->logStockMovement(
                    $movimientoDestino,
                    $newProductoMovimiento,
                    'traslado-campos-entrada',
                    $newProductoMovimiento->cantidad,
                    "Entrada desde traslado entre campos desde {$transfer->tenantOrigen->name}",
                    $movimientoDestino->bodega_destino_id
                );
            }

            $transfer->update([
                'estado' => 'aprobado',
                'movimiento_destino_id' => $movimientoDestino->id,
            ]);

            Log::info("Transferencia inter-tenant aprobada: ID {$transfer->id}");
        });
    }

    /**
     * Rechaza una transferencia inter-tenant y reabre el movimiento de origen.
     */
    public function rejectInterTenantTransfer(InterTenantTransfer $transfer, int $userId): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->estado !== 'pendiente') {
                Log::warning("Intento de rechazar transferencia ya procesada: ID {$transfer->id}");
                throw new \Exception('La transferencia ya ha sido procesada.');
            }

            $movimientoOrigen = $transfer->movimientoOrigen;
            $productoMovimientos = $movimientoOrigen->movimientoProductos()->where('inter_tenant_transfer_id', $transfer->id)->get();

            foreach ($productoMovimientos as $productoMovimiento) {
                $stockOrigen = $this->getStock(
                    $productoMovimiento->producto_id,
                    $movimientoOrigen->bodega_origen_id,
                    $movimientoOrigen->field_id
                );

                $this->updateStock(
                    $stockOrigen,
                    $productoMovimiento->cantidad,
                    null,
                    $userId,
                    $movimientoOrigen,
                    $productoMovimiento
                );

                $this->logStockMovement(
                    $movimientoOrigen,
                    $productoMovimiento,
                    'traslado-campos-rechazado',
                    $productoMovimiento->cantidad,
                    "Rechazo de traslado entre campos desde {$transfer->tenantDestino->name}",
                    $movimientoOrigen->bodega_origen_id
                );
            }

            $movimientoOrigen->is_completed = false;
            $movimientoOrigen->save();

            $transfer->update([
                'estado' => 'rechazado',
            ]);

            Log::info("Transferencia inter-tenant rechazada: ID {$transfer->id}. Movimiento ID: {$movimientoOrigen->id} reabierto.");
        });
    }

    /**
     * Revierte el impacto anterior de un movimiento.
     */
    private function revertPreviousImpact(MovementType $tipo, Movimiento $movimiento, MovimientoProducto $productoMovimiento, float $cantidadAnterior): void
    {
        $producto = $productoMovimiento->producto;

        switch ($tipo) {
            case MovementType::ENTRADA:
                if ($producto->requiresBatchControl()) {
                    $batch = Batch::where([
                        'product_id' => $producto->id,
                        'lot_number' => $productoMovimiento->lot_number,
                        'expiration_date' => $productoMovimiento->expiration_date,
                    ])->first();

                    if ($batch) {
                        $batch->delete();
                        Log::info("Lote eliminado: ID {$batch->id}");
                    }
                }

                $stockDestino = $this->getStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id
                );

                $this->updateStock($stockDestino, -$cantidadAnterior, null, $movimiento->user_id, $movimiento, $productoMovimiento);
                break;

            case MovementType::SALIDA:
            case MovementType::PREPARACION:
            case MovementType::TRASLADO_CAMPOS:
                if ($movimiento->bodega_origen_id) {
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidadAnterior, $producto->price, $movimiento->user_id, $movimiento, $productoMovimiento);
                }
                break;

            case MovementType::TRASLADO:
                $stockOrigen = $movimiento->bodega_origen_id
                    ? $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id)
                    : null;

                $stockDestino = $this->getStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id
                );

                if ($stockOrigen) {
                    $this->updateStock($stockOrigen, $cantidadAnterior, null, $movimiento->user_id, $movimiento, $productoMovimiento);
                }
                $this->updateStock($stockDestino, -$cantidadAnterior, null, $movimiento->user_id, $movimiento, $productoMovimiento);
                break;

            default:
                Log::warning("Tipo de movimiento no reconocido para revertir: {$tipo->value}");
                throw new InvalidMovementTypeException("Tipo de movimiento no válido: {$tipo->value}");
        }
    }

    /**
     * Crea un nuevo lote para productos con control de lotes.
     */
    private function createBatch(MovimientoProducto $productoMovimiento, float $cantidad, Movimiento $movimiento): void
    {
        $producto = $productoMovimiento->producto;

        Batch::create([
            'product_id' => $producto->id,
            'quantity' => $cantidad,
            'expiration_date' => $productoMovimiento->expiration_date,
            'lot_number' => $productoMovimiento->lot_number,
            'buy_order' => $movimiento->orden_compra,
            'invoice_number' => $movimiento->guia_despacho,
            'provider' => $movimiento->nombre_proveedor,
        ]);

        Log::info("Lote creado: Producto {$producto->product_name}, Cantidad {$cantidad}, Bodega ID {$movimiento->bodega_destino_id}");
    }

    /**
     * Obtiene el stock existente o lanza una excepción.
     */
    private function getStock(int $productoId, int $warehouseId, int $fieldId): Stock
    {
        $stock = Stock::where([
            'product_id' => $productoId,
            'warehouse_id' => $warehouseId,
            'field_id' => $fieldId,
        ])->first();

        if (!$stock) {
            Log::error("Stock no encontrado para producto ID: {$productoId}, bodega ID: {$warehouseId}, campo ID: {$fieldId}");
            throw new WarehouseNotFoundException("Stock no encontrado en la bodega ID: {$warehouseId}.");
        }

        return $stock;
    }

    /**
     * Obtiene o crea el stock para una bodega.
     */
    private function getOrCreateStock(int $productoId, int $warehouseId, int $fieldId, float $precioCompra, int $userId): Stock
    {
        if (is_null($warehouseId)) {
            Log::error('Bodega de destino no especificada.');
            throw new WarehouseNotFoundException('Bodega de destino no especificada.');
        }

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
     * Actualiza el stock y valida la cantidad.
     */
    private function updateStock(Stock $stock, float $cantidadCambio, ?float $nuevoPrecio, int $userId, Movimiento $movimiento, MovimientoProducto $productoMovimiento): void
    {
        if ($stock->quantity + $cantidadCambio < 0) {
            Log::error("Stock insuficiente: Producto ID {$stock->product_id}, Bodega ID {$stock->warehouse_id}");
            throw new InsufficientStockException('Stock insuficiente para realizar la operación.');
        }

        $stock->quantity += $cantidadCambio;
        if ($nuevoPrecio !== null) {
            $stock->price = $nuevoPrecio;
        }
        $stock->updated_by = $userId;
        $stock->save();

        Log::info("Stock actualizado: Producto ID {$stock->product_id}, Bodega ID {$stock->warehouse_id}, Cambio: {$cantidadCambio}");
    }

    /**
     * Registra un movimiento de stock.
     */
    private function logStockMovement(Movimiento $movimiento, MovimientoProducto $productoMovimiento, string|MovementType $tipoMovimiento, float $cantidad, ?string $descripcion, ?int $warehouseId): void
    {
        $tipoString = is_string($tipoMovimiento) ? $tipoMovimiento : $tipoMovimiento->value;

        if ($warehouseId === null) {
            $warehouseId = match ($tipoString) {
                MovementType::SALIDA->value, MovementType::PREPARACION->value, 'traslado-salida', 'traslado-campos-salida', 'traslado-campos-rechazado' => $movimiento->bodega_origen_id,
                MovementType::ENTRADA->value, 'traslado-entrada', 'traslado-campos-entrada' => $movimiento->bodega_destino_id,
                default => null,
            };
        }

        $descripcionFinal = $descripcion ?? "Movimiento {$productoMovimiento->id} de tipo {$tipoString} registrado.";

        StockMovement::create([
            'movement_type' => $tipoString,
            'product_id' => $productoMovimiento->producto_id,
            'warehouse_id' => $warehouseId,
            'related_id' => $productoMovimiento->id,
            'related_type' => MovimientoProducto::class,
            'quantity_change' => $cantidad,
            'description' => $descripcionFinal,
            'user_id' => $movimiento->user_id,
            'field_id' => $movimiento->field_id,
            'updated_by' => $movimiento->updated_by ?? $movimiento->user_id,
        ]);

        Log::info("Movimiento registrado: Tipo {$tipoString}, Producto ID {$productoMovimiento->producto_id}, Cantidad {$cantidad}, Bodega {$warehouseId}, Descripción: {$descripcionFinal}");
    }

    /**
     * Revierte el impacto de un movimiento de producto.
     */
    public function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        Log::info("Revirtiendo impacto de stock para MovimientoProducto ID: {$productoMovimiento->id}");

        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidad = $productoMovimiento->cantidad;
        $userId = Auth::id() ?? 1;
        $producto = $productoMovimiento->producto;

        DB::transaction(function () use ($tipo, $movimiento, $productoMovimiento, $cantidad, $userId, $producto) {
            switch ($tipo) {
                case MovementType::ENTRADA:
                    if ($producto->requiresBatchControl()) {
                        $batch = Batch::where([
                            'product_id' => $producto->id,
                            'lot_number' => $productoMovimiento->lot_number,
                            'expiration_date' => $productoMovimiento->expiration_date,
                        ])->first();

                        if ($batch) {
                            $batch->delete();
                            Log::info("Lote eliminado: Producto ID {$producto->id}, Lote ID {$batch->id}");
                        }
                    }

                    $stockDestino = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_destino_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockDestino, -$cantidad, null, $userId, $movimiento, $productoMovimiento);
                    break;

                case MovementType::SALIDA:
                case MovementType::PREPARACION:
                case MovementType::TRASLADO_CAMPOS:
                    $stockOrigen = $this->getStock(
                        $productoMovimiento->producto_id,
                        $movimiento->bodega_origen_id,
                        $movimiento->field_id
                    );
                    $this->updateStock($stockOrigen, $cantidad, null, $userId, $movimiento, $productoMovimiento);
                    break;

                case MovementType::TRASLADO:
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
                    $this->updateStock($stockOrigen, $cantidad, null, $userId, $movimiento, $productoMovimiento);
                    $this->updateStock($stockDestino, -$cantidad, null, $userId, $movimiento, $productoMovimiento);
                    break;
            }

            Log::info("Stock revertido para MovimientoProducto ID: {$productoMovimiento->id}");
        });
    }

    /**
     * Valida si hay stock suficiente antes de crear un movimiento.
     */
    public function validateStockBeforeCreating(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = MovementType::from(strtolower($movimiento->tipo->value));
        $cantidad = $productoMovimiento->cantidad;

        if (in_array($tipo, [MovementType::SALIDA, MovementType::PREPARACION, MovementType::TRASLADO, MovementType::TRASLADO_CAMPOS])) {
            $stockOrigen = $this->getStock(
                $productoMovimiento->producto_id,
                $movimiento->bodega_origen_id,
                $movimiento->field_id
            );

            if ($stockOrigen->quantity < $cantidad) {
                Log::error("Stock insuficiente para {$tipo->value}: Producto ID {$productoMovimiento->producto_id}, Bodega ID {$movimiento->bodega_origen_id}");
                throw new InsufficientStockException("Stock insuficiente para {$tipo->value}.");
            }
        }
    }

    /**
     * Genera un número único para un movimiento.
     */
    private function generateUniqueMovementNumber(): string
    {
        $latestMovement = Movimiento::latest('id')->first();
        $nextNumber = $latestMovement ? $latestMovement->id + 1 : 1;
        $date = date('Y-m-d');
        return $date . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}