<?php

namespace App\Models;

use App\Enums\StatusType;
use App\Enums\MovementType;
use Filament\Facades\Filament;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Para depuración opcional
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use App\Services\StockService; // Importa el servicio si no está global

class MovimientoProducto extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    
    protected $fillable = [
        'movimiento_id',
        'producto_id',
        'field_id',
        'precio_compra',
        'unidad_medida',
        'cantidad',
        'created_by',
        'updated_by',
        'lot_number',
        'expiration_date',
        'total',
        'package_id',
        'cantidad_envases',
    ];
    
    protected $casts = [
        'cantidad' => 'float',
        'total' => 'float',
        'precio_compra' => 'float',
    ];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    
    protected static function booted()
    {
        static::creating(function ($movimientoProducto) {
            $movimientoProducto->created_by = Auth::id();
            $movimientoProducto->field_id = Filament::getTenant()->id;
            
            // Asignar precio_compra desde el producto si no está seteado
            if (empty($movimientoProducto->precio_compra) && $movimientoProducto->producto_id) {
                $producto = $movimientoProducto->producto;
                if ($producto) {
                    $movimientoProducto->precio_compra = $producto->price ?? 0; 
                } else {
                    throw new \Exception('No se puede crear el movimiento: producto no encontrado.');
                }
            }
            
            // Calcular total basado en cantidad y precio_compra
            $movimientoProducto->total = ($movimientoProducto->cantidad ?? 0) * ($movimientoProducto->precio_compra ?? 0);
            
            // Calcular cantidad de envases si hay un package seleccionado (descomentar si es necesario)
            /* if ($movimientoProducto->package_id) {
                $package = Package::find($movimientoProducto->package_id);
                if ($package) {
                    $movimientoProducto->cantidad_envases = ceil($movimientoProducto->cantidad / $package->capacity);
                }
            } */
        });
    
        static::created(function ($movimientoProducto) {
            // Crear MovimientoEnvase si aplica (descomentar si es necesario)
            /* if ($movimientoProducto->package_id) {
                MovimientoEnvase::create([
                    'movimiento_producto_id' => $movimientoProducto->id,
                    'package_id' => $movimientoProducto->package_id,
                    'cantidad_envases' => $movimientoProducto->cantidad_envases,
                ]);
            } */

            // Procesar cambios de status solo para movimientos de entrada
            if ($movimientoProducto->movimiento->tipo === MovementType::ENTRADA) {
                $movimientoProducto->actualizarStatuses();
            }
        });

        static::deleted(function ($movimientoProducto) {
            // Procesar actualización de status solo para movimientos de entrada
            if ($movimientoProducto->movimiento->tipo === MovementType::ENTRADA) {
                $movimientoProducto->actualizarStatusesPostEliminacion();
            }
        });

        static::updating(function ($movimientoProducto) {
            // Recalcular cantidad de envases si aplica (descomentar si es necesario)
            /* if ($movimientoProducto->package_id) {
                $package = Package::find($movimientoProducto->package_id);
                if ($package && !$movimientoProducto->isDirty('cantidad_envases')) {
                    $movimientoProducto->cantidad_envases = ceil($movimientoProducto->cantidad / $package->capacity);
                }
            } */
        });
        
        static::updated(function ($movimientoProducto) {
            // Actualizar o crear MovimientoEnvase (descomentar si es necesario)
            /* $envase = MovimientoEnvase::where('movimiento_producto_id', $movimientoProducto->id)->first();

            if ($envase) {
                $envase->update([
                    'cantidad_envases' => $movimientoProducto->cantidad_envases,
                ]);
            } else {
                if ($movimientoProducto->package_id) {
                    MovimientoEnvase::create([
                        'movimiento_producto_id' => $movimientoProducto->id,
                        'package_id' => $movimientoProducto->package_id,
                        'cantidad_envases' => $movimientoProducto->cantidad_envases,
                    ]);
                }
            } */

            // Procesar cambios de status solo para movimientos de entrada si se actualiza cantidad
            if ($movimientoProducto->movimiento->tipo === MovementType::ENTRADA && $movimientoProducto->isDirty('cantidad')) {
                $movimientoProducto->actualizarStatusesPostActualizacion();
            }
        });
    }

    /**
     * Actualiza los statuses de PurchaseOrderDetail y PurchaseOrder basado en la recepción (para creación y actualización).
     */
    protected function actualizarStatuses()
    {
        $ordenCompra = $this->getOrdenCompra();
        if (!$ordenCompra) {
            return;
        }

        // Actualizar status del detalle
        $detalle = $this->getDetalle($ordenCompra);
        if ($detalle) {
            $cantidadRecibida = $this->calcularCantidadRecibidaDetalle($ordenCompra, $detalle->product_id);

            // Cambiar a ENPROCESO si hay algo recibido pero no completo
            if ($cantidadRecibida > 0 && $cantidadRecibida < $detalle->quantity) {
                $detalle->status = StatusType::ENPROCESO;
            } elseif ($cantidadRecibida >= $detalle->quantity) {
                $detalle->status = StatusType::COMPLETO;
            } elseif ($cantidadRecibida == 0) {
                $detalle->status = StatusType::PENDIENTE;
            }

            $detalle->save();
        }

        // Actualizar status de la orden
        $cantidadRecibidaOrden = $this->calcularCantidadRecibidaOrden($ordenCompra);
        $cantidadOrdenadaTotal = $ordenCompra->PurchaseOrderDetails()->sum('quantity');

        // Cambiar status de la orden
        if ($cantidadRecibidaOrden == 0) {
            $ordenCompra->status = StatusType::PENDIENTE;
        } elseif ($cantidadRecibidaOrden > 0 && $cantidadRecibidaOrden < $cantidadOrdenadaTotal) {
            $ordenCompra->status = StatusType::ENPROCESO;
        } elseif ($cantidadRecibidaOrden >= $cantidadOrdenadaTotal) {
            $ordenCompra->status = StatusType::COMPLETO;
        }

        $ordenCompra->save();
    }

    /**
     * Actualiza los statuses después de la eliminación (similar a actualizarStatuses pero recalcula todo).
     */
    protected function actualizarStatusesPostEliminacion()
    {
        $this->actualizarStatuses(); // Reutiliza la lógica general de actualización
    }

    /**
     * Actualiza los statuses después de una actualización en cantidad (similar a actualizarStatuses).
     */
    protected function actualizarStatusesPostActualizacion()
    {
        $this->actualizarStatuses(); // Reutiliza la lógica general de actualización
    }

    /**
     * Obtiene la orden de compra asociada.
     */
    private function getOrdenCompra()
    {
        $movimiento = $this->movimiento;
        return $movimiento->purchaseOrder;
    }

    /**
     * Obtiene el detalle asociado por product_id.
     */
    private function getDetalle($ordenCompra)
    {
        $detalle = $ordenCompra->PurchaseOrderDetails()->where('product_id', $this->producto_id)->first();
        if (!$detalle) {
            Log::warning("Detalle no encontrado para product_id {$this->producto_id} en orden {$ordenCompra->id}");
        }
        return $detalle;
    }

    /**
     * Calcula la cantidad recibida actual para un detalle específico (incluyendo todos los movimientos).
     */
    private function calcularCantidadRecibidaDetalle($ordenCompra, $productId)
    {
        return MovimientoProducto::whereHas('movimiento', fn($q) => $q->where('purchase_order_id', $ordenCompra->id)->where('tipo', MovementType::ENTRADA))
            ->where('producto_id', $productId)
            ->sum('cantidad');
    }

    /**
     * Calcula la cantidad recibida actual para toda la orden (incluyendo todos los movimientos).
     */
    private function calcularCantidadRecibidaOrden($ordenCompra)
    {
        return MovimientoProducto::whereHas('movimiento', fn($q) => $q->where('purchase_order_id', $ordenCompra->id)->where('tipo', MovementType::ENTRADA))
            ->sum('cantidad');
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'related');
    }
    
    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }
    
    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    
    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }
    
    public function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }
    
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
    
    public function movimientoEnvases()
    {
        return $this->hasMany(MovimientoEnvase::class, 'movimiento_producto_id');
    }
}