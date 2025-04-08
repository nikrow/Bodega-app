<?php

namespace App\Models;


use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MovimientoEnvase extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'field_id',
        'movimiento_producto_id',
        'package_id',
        'cantidad_envases',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($movimientoEnvase) {
            $movimientoEnvase->field_id = Filament::getTenant()->id;
            
        });
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function movimientoProducto()
    {
        return $this->belongsTo(MovimientoProducto::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
