<?php

namespace App\Models;

use App\Enums\StatusType;
use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class PurchaseOrder extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    
    protected $fillable = [
        'user_id',
        'field_id',
        'number',
        'provider_id',
        'date',
        'status',
        'is_received',
        'observation',
    ];

    protected $casts = [
        'status' => StatusType::class,
        'is_received' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->user_id = Auth::id();
            $order->field_id = Filament::getTenant()->id;
            $order->status = StatusType::PENDIENTE;
            $order->is_received = false;
        });
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }
    
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    
    public function product()
    {
        return $this->hasMany(Product::class);
    }
    
    public function PurchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }
    
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class)->where('purchase_order_id', $this->id);
    }
    
    public function movimientoProductos()
    {
        return $this->hasManyThrough(
            MovimientoProducto::class,
            Movimiento::class,
            'purchase_order_id', // Foreign key en Movimiento (reemplaza orden_compra)
            'movimiento_id',     // Foreign key en MovimientoProducto
            'id',                // Local key en PurchaseOrder
            'id'                 // Local key en Movimiento
        )->where('tipo', \App\Enums\MovementType::ENTRADA);
    }
}