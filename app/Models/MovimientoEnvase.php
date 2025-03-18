<?php

namespace App\Models;


use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MovimientoEnvase extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_id',
        'movimiento_producto_id',
        'package_id',
        'cantidad_envases',
    ];
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
