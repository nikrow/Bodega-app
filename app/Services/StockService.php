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
    public function applyStockChanges(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = $movimiento->tipo->value;
        $cantidad = $productoMovimiento->cantidad;


        // Capturamos el ID del usuario autenticado
        $userId = Auth::id();

        // Validación inicial para evitar null en bodega_origen_id o bodega_destino_id
        if ($tipo !== 'entrada' && is_null($movimiento->bodega_origen_id)) {
            Log::error('El ID de la bodega de origen es nulo para el movimiento ID: ' . $movimiento->id);
            throw new Exception('El ID de la bodega de origen no puede ser nulo.');
        }

        if (($tipo === 'entrada' || $tipo === 'traslado') && is_null($movimiento->bodega_destino_id)) {
            Log::error('El ID de la bodega de destino es nulo para el movimiento ID: ' . $movimiento->id);
            throw new Exception('El ID de la bodega de destino no puede ser nulo.');
        }

        // Obtener el producto asociado al movimiento
        $producto = $productoMovimiento->producto;
        if (!$producto) {
            Log::error('Producto no encontrado para el movimientoProducto ID: ' . $productoMovimiento->id);
            throw new Exception('Producto no encontrado.');
        }

        // Obtener el precio de compra desde la tabla Products si es una entrada
        $precioCompra = null;
        if ($tipo === 'entrada') {
            $precioCompra = $producto->price;
            if ($precioCompra === null) {
                Log::error('El precio de compra es nulo para el producto ID: ' . $producto->id);
                throw new Exception('El precio de compra no puede ser nulo.');
            }

            // Actualizar el precio en la tabla Products
        $producto->price = $precioCompra; // Si deseas actualizarlo, ajusta según tus necesidades
            $producto->save();
        } elseif ($tipo === 'traslado') {

        // En traslados, podrías querer mantener el precio original o ajustarlo según tu lógica
        $precioCompra = $producto->price; // O cualquier otra lógica
        } else {

        // Para salidas, el precio de compra no es necesario
        $precioCompra = null;
            }
        $stockOrigen = null;
            if ($tipo === 'salida' || $tipo === 'traslado') {
                $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);
            }

        $stockDestino = null;
            if ($tipo === 'entrada' || $tipo === 'traslado') {
                $stockDestino = $this->getOrCreateStock($productoMovimiento->producto_id, $movimiento->bodega_destino_id, $movimiento->field_id, $precioCompra, $userId);
            }

        // Manejo de los diferentes tipos de movimiento
        switch ($tipo) {
            case 'entrada':
                $this->updateStock($stockDestino, $cantidad, $precioCompra, $userId);
                break;
            case 'salida':
                $this->handleSalida($stockOrigen, $cantidad, $userId);
                break;
            case 'traslado':
                $this->handleTraslado($stockOrigen, $stockDestino, $cantidad, $precioCompra, $userId);
                break;
            default:
                throw new Exception('Tipo de movimiento no válido.');
        }
    }

    // Método auxiliar para obtener el stock, o lanzará una excepción si no se encuentra
    private function getStock(int $productoId, int $wharehouseId, int $fieldId): ?Stock
    {
        Log::info("Buscando stock para producto ID: $productoId, bodega ID: $wharehouseId, campo ID: $fieldId");

        $stock = Stock::where([
            'product_id' => $productoId,
            'wharehouse_id' => $wharehouseId,
            'field_id' => $fieldId,
        ])->first();

        if (!$stock) {
            Log::error("Stock no encontrado para el producto ID: $productoId en bodega ID: $wharehouseId.");
            throw new Exception("Stock no encontrado en la bodega especificada.");
        }

        return $stock;
    }

    // Método auxiliar para obtener o crear el stock de destino
    private function getOrCreateStock(int $productoId, int $wharehouseId, int $fieldId, float $precioCompra, int $userId): Stock
    {
        Log::info("Obteniendo o creando stock para producto ID: $productoId, bodega ID: $wharehouseId, campo ID: $fieldId");

        return Stock::firstOrCreate([
            'product_id' => $productoId,
            'wharehouse_id' => $wharehouseId,
            'field_id' => $fieldId,
        ], [
            'quantity' => 0,
            'price' => $precioCompra,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    // Método auxiliar para actualizar el stock
    private function updateStock(Stock $stock, int $cantidadCambio, ?float $nuevoPrecio, int $userId): void
    {
        Log::info("Actualizando stock ID: {$stock->id}, Cambio de cantidad: $cantidadCambio");

        $stock->quantity += $cantidadCambio;
        if ($nuevoPrecio !== null) {
            $stock->price = $nuevoPrecio;
        }
        $stock->updated_by = $userId;
        $stock->save();
    }

    // Manejar salida, validando que haya suficiente stock
    private function handleSalida(?Stock $stockOrigen, int $cantidad, int $userId): void
    {
        if ($stockOrigen && $stockOrigen->quantity >= $cantidad) {
            $this->updateStock($stockOrigen, -$cantidad, null, $userId); // Restar cantidad
        } else {
            Log::error('Stock insuficiente para salida.');
            throw new Exception('Stock insuficiente para salida.');
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
    public function revertProductMovementImpact(MovimientoProducto $productoMovimiento): void
    {
        $movimiento = $productoMovimiento->movimiento;
        $tipo = $movimiento->tipo->value;
        $cantidad = $productoMovimiento->cantidad;
        $userId = Auth::id();

        // Obtener stock de origen y destino
        $stockOrigen = $this->getStock($productoMovimiento->producto_id, $movimiento->bodega_origen_id, $movimiento->field_id);
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
