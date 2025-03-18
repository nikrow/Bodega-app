<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class MovimientoProducto extends Model
{
    use HasFactory;
    use LogsActivity;
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
            $movimientoProducto->total = $movimientoProducto->cantidad * $movimientoProducto->precio_compra;

            // Calcular cantidad de envases si hay un package seleccionado
            if ($movimientoProducto->package_id) {
                $package = Package::find($movimientoProducto->package_id);
                if ($package) {
                    $movimientoProducto->cantidad_envases = ceil($movimientoProducto->cantidad / $package->capacity);
                }
            }
        });
    
        static::created(function ($movimientoProducto) {
            if ($movimientoProducto->package_id) {
                MovimientoEnvase::create([
                    'movimiento_producto_id' => $movimientoProducto->id,
                    'package_id' => $movimientoProducto->package_id,
                    'cantidad_envases' => $movimientoProducto->cantidad_envases,
                ]);
            }
        });

        static::updating(function ($movimientoProducto) {
            // Solo recalcular si el usuario no ha modificado manualmente la cantidad de envases
            if ($movimientoProducto->package_id) {
                $package = Package::find($movimientoProducto->package_id);
                if ($package && !$movimientoProducto->isDirty('cantidad_envases')) {
                    $movimientoProducto->cantidad_envases = ceil($movimientoProducto->cantidad / $package->capacity);
                }
            }
        });
        static::updated(function ($movimientoProducto) {
            // Actualizar la cantidad de envases en MovimientoEnvase
            $envase = MovimientoEnvase::where('movimiento_producto_id', $movimientoProducto->id)->first();

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
            }
        });
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
