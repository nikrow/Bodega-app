<?php

namespace App\Services;

use App\Models\MovimientoProducto;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockService
{
    public function applyStockChanges(MovimientoProducto $productoMovimiento, ?int $cantidadAnterior = null): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = $movimiento->tipo->value;
        $cantidadNueva = $productoMovimiento->cantidad;

        // Capturamos el ID del usuario autenticado
        $userId = Auth::id();

        // Obtener el producto asociado al movimiento
        $producto = $productoMovimiento->producto;
        if (!$producto) {
            Log::error('Producto no encontrado para el movimientoProducto ID: ' . $productoMovimiento->id);
            throw new Exception('Producto no encontrado.');
        }

        // Revertir el impacto anterior del stock (si la cantidad anterior existe)
        if ($cantidadAnterior !== null) {
            if ($tipo === 'entrada') {
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->updateStock($stockDestino, -$cantidadAnterior, null, $userId);
            } elseif ($tipo === 'traslado') {
                if (!is_null($movimiento->bodega_origen_id)) {
                    $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);
                }
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->handleRevertTraslado($stockOrigen ?? null, $stockDestino, $cantidadAnterior, $userId);
            } elseif ($tipo === 'salida') {
                if (!is_null($movimiento->bodega_origen_id)) {
                    $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);
                    $this->updateStock($stockOrigen, $cantidadAnterior, $producto->price, $userId); // Revertir salida
                }
            }
        }

        // Aplicar el nuevo impacto de stock
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
                break;

            case 'salida':
                if (!is_null($movimiento->bodega_origen_id)) {
                    $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);
                    $this->handleSalida($stockOrigen, $cantidadNueva, $producto->price, $userId);
                }
                break;

            case 'traslado':
                if (!is_null($movimiento->bodega_origen_id)) {
                    $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);
                }
                $stockDestino = $this->getOrCreateStock(
                    $productoMovimiento->producto_id,
                    $movimiento->bodega_destino_id,
                    $movimiento->field_id,
                    $producto->price,
                    $userId
                );
                $this->handleTraslado($stockOrigen ?? null, $stockDestino, $cantidadNueva, $producto->price, $userId);
                break;

            default:
                throw new Exception('Tipo de movimiento no válido.');
        }
    }

    // Método auxiliar para obtener el stock, o lanzará una excepción si no se encuentra
    private function getStock(int $productoId, ?int $warehouseId, int $fieldId): ?Stock
    {
        if (is_null($warehouseId)) {
            return null;
        }

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

    // Método auxiliar para obtener o crear el stock de destino
    private function getOrCreateStock(int $productoId, ?int $warehouseId, int $fieldId, float $precioCompra, int $userId): Stock
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

    // Método auxiliar para actualizar el stock
    private function updateStock(Stock $stock, int $cantidadCambio, ?float $nuevoPrecio, int $userId): ?string
    {
        // Verificar que no se exceda el stock mínimo
        if ($stock->quantity + $cantidadCambio < 0) {
            return "Stock insuficiente para realizar esta operación. Cantidad disponible en stock: {$stock->quantity}";
        }

        // Actualizar la cantidad del stock
        $stock->quantity += $cantidadCambio;
        if ($nuevoPrecio !== null) {
            $stock->price = $nuevoPrecio;
        }
        $stock->updated_by = $userId;
        $stock->save();

        return null; // Sin errores
    }

    private function handleSalida(?Stock $stockOrigen, int $cantidad, float $precioCompra, int $userId): ?string
    {
        if ($stockOrigen && $stockOrigen->quantity >= $cantidad) {
            return $this->updateStock($stockOrigen, -$cantidad, $precioCompra, $userId); // Restar cantidad y usar precio
        } else {
            return 'Stock insuficiente para realizar la salida.';
        }
    }


    // Manejar traslado entre bodegas
    private function handleTraslado(?Stock $stockOrigen, Stock $stockDestino, int $cantidad, float $precioCompra, int $userId): void
    {
        DB::transaction(function () use ($stockOrigen, $stockDestino, $cantidad, $precioCompra, $userId) {
            if ($stockOrigen && $stockOrigen->quantity >= $cantidad) {
                $this->updateStock($stockOrigen, -$cantidad, null, $userId); // Restar cantidad en origen
                $this->updateStock($stockDestino, $cantidad, $precioCompra, $userId); // Sumar cantidad en destino
            } else {
                Log::error('Stock insuficiente para traslado.');
                throw new Exception('Stock insuficiente para traslado.');
            }
        });
    }

    // Manejar la reversión del traslado entre bodegas
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

    // Revertir el impacto de un producto en el movimiento
    public function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = $movimiento->tipo->value;
        $cantidad = $productoMovimiento->cantidad;
        $userId = Auth::id();

        // Obtener stock de origen y destino
        $stockOrigen = $tipo !== 'entrada' ? $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id) : null;
        $stockDestino = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_destino_id, $movimiento->field_id);

        switch ($tipo) {
            case 'entrada':
                $this->updateStock($stockDestino, -$cantidad, null, $userId); // Restar cantidad
                break;
            case 'salida':
                $this->updateStock($stockOrigen, $cantidad, null, $userId); // Sumar cantidad
                break;
            case 'traslado':
                $this->handleRevertTraslado($stockOrigen, $stockDestino, $cantidad, $userId);
                break;
            default:
                throw new Exception('Tipo de movimiento no válido.');
        }
    }

    // Validar que haya stock suficiente antes de crear un movimiento
    public function validateStockBeforeCreating(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = $movimiento->tipo->value;
        $cantidad = $productoMovimiento->cantidad;

        if ($tipo === 'salida') {
            $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);

            if ($stockOrigen->quantity < $cantidad) {
                throw new Exception('Stock insuficiente para salida.');
            }
        }
    }
}
