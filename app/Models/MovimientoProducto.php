<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MovimientoProducto extends Model
{
    use HasFactory;
    protected $fillable = [
        'movimiento_id',
        'producto_id',
        'precio_compra',
        'unidad_medida',
        'cantidad',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->updated_by = Auth::id();
        });
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
}
