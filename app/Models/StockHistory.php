<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockHistory extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use LogsActivity;

    protected $fillable = [
        'stock_id',
        'product_id',
        'warehouse_id',
        'field_id',
        'quantity_snapshot',
        'price_snapshot',
        'movement_type',
        'movement_id',
        'movement_product_id',
        'created_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'movement_id');
    }

    public function movimientoProducto()
    {
        return $this->belongsTo(MovimientoProducto::class, 'movement_product_id');
    }

}
