<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterTenantTransfer extends Model
{
    protected $fillable = [
        'tenant_origen_id',
        'tenant_destino_id',
        'bodega_origen_id',
        'bodega_destino_id',
        'movimiento_origen_id',
        'movimiento_destino_id',
        'orden_compra_id',
        'estado',
        'user_id',
    ];
    protected $casts = [
        'estado' => 'string',
    ];

    public function tenantOrigen()
    {
        return $this->belongsTo(Field::class, 'tenant_origen_id');
    }

    public function tenantDestino()
    {
        return $this->belongsTo(Field::class, 'tenant_destino_id');
    }

    public function bodegaOrigen()
    {
        return $this->belongsTo(Warehouse::class, 'bodega_origen_id');
    }

    public function bodegaDestino()
    {
        return $this->belongsTo(Warehouse::class, 'bodega_destino_id');
    }

    public function movimientoOrigen()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_origen_id');
    }

    public function movimientoDestino()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_destino_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'orden_compra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function movimientoProductos()
    {
        return $this->hasMany(MovimientoProducto::class, 'inter_tenant_transfer_id');
    }
    
}