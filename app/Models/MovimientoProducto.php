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
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->field_id = Filament::getTenant()->id;
            $field->total = $field->cantidad * $field->precio_compra;

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
}
