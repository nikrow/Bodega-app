<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class Movement extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'product_id',
        'field_id',
        'bodega_origen_id',
        'bodega_destino_id',
        'cantidad',
        'tipo',
        'descripcion',
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
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function bodega_origen()
    {
        return $this->belongsTo(Wharehouse::class, 'bodega_origen_id');
    }

    public function bodega_destino()
    {
        return $this->belongsTo(Wharehouse::class, 'bodega_destino_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }
}
