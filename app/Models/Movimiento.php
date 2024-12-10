<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Movimiento extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'user_id',
        'movement_number',
        'tipo',
        'cantidad',
        'comprobante',
        'encargado',
        'field_id',
        'bodega_origen_id',
        'bodega_destino_id',
        'orden_compra',
        'nombre_proveedor',
        'guia_despacho',
        'is_completed',
        'created_by',
        'updated_by',
        'order_id',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    private static function generateUniqueMovementNumber()
    {
        // Obtenemos el último movimiento creado
        $latestMovement = self::latest('id')->first();

        // Calculamos el siguiente número incrementando el último ID
        $nextNumber = $latestMovement ? $latestMovement->id + 1 : 1;

        // Formateamos la fecha en el formato deseado (Año-Mes-Día)
        $date = date('Y-m-d');

        // Devolvemos el número de movimiento con la fecha y el número incremental
        return $date . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        // Ejemplo: 2024-09-30-000001
    }

    // Casting de Enum
    protected $casts = [
        'tipo' => MovementType::class,
        'is_completed' => 'boolean',
    ];
    protected static function booted()
    {
        static::creating(function ($movement) {
            $movement->movement_number = self::generateUniqueMovementNumber();
            $movement->created_by = Auth::id();
            $movement->user_id = Auth::id();
            $movement->updated_by = Auth::id();
        });

        static::updating(function ($movimiento) {
            // Si el movimiento ya fue completado, no se permiten modificaciones excepto cambiar 'is_completed'.
            if ($movimiento->is_completed) {
                $cambiosRelevantes = collect($movimiento->getDirty())->except(['is_completed']);

                if ($cambiosRelevantes->isNotEmpty()) {
                    // Se intenta modificar otros campos distintos a 'is_completed', lanzar excepción
                    throw ValidationException::withMessages([
                        'is_completed' => 'No puedes modificar un movimiento que ya ha sido completado.',
                    ]);
                }
            }
            $movimiento->updated_by = Auth::id();
        });

    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con el usuario que actualizó el movimiento
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function movimientoProductos()
    {
        return $this->hasMany(MovimientoProducto::class, 'movimiento_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }
    // Relación con Warehouse (Bodega de Origen)
    public function bodega_origen()
    {
        return $this->belongsTo(Warehouse::class, 'bodega_origen_id');
    }

    /**
     * Relación con la bodega de destino.
     */
    public function bodega_destino()
    {
        return $this->belongsTo(Warehouse::class, 'bodega_destino_id');
    }

    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
