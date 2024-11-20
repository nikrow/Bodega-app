<?php

namespace App\Models;

use App\Enums\StatusType;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockMovement extends Model
{
    use HasFactory;
    use LogsActivity;


    protected $fillable = [
        'movement_type',
        'product_id',
        'warehouse_id',
        'related_id',
        'related_type',
        'quantity_change',
        'description',
        'user_id',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($stockMovement) {

            $stockMovement->user_id = Auth::id();
            $stockMovement->field_id = Filament::getTenant()->id;
        });

    }
    /**
     * Obtener el modelo relacionado (Movimiento o OrderApplicationUsage).
     */
    public function related()
    {
        return $this->morphTo();
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
    public function movimientoProducto()
    {
        return $this->belongsTo(MovimientoProducto::class, 'related_id');
    }

    public function movimiento()
    {
        return $this->movimientoProducto ? $this->movimientoProducto->movimiento : null;
    }
    public function getMovementNumberAttribute(): ?string
    {
        return $this->movimientoProducto ? $this->movimientoProducto->movimiento->movement_number : null;
    }

    /**
     * Relación con el producto.
     */
    public function producto()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relación con la bodega.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relación con el usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
