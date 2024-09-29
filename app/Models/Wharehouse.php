<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class Wharehouse extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'status',
        'field_id',
        'slug',
        'created_by',
        'updated_by',
    ];



    protected static function booted()
    {
        static::creating(function ($field) {
            // Asigna el ID del usuario autenticado
            $field->created_by = Auth::id();
        });

        static::creating(function ($field) {
            // Genera el slug a partir del nombre
            $field->slug = Str::slug($field->name); // Cambié 'title' por 'name'
        });

        static::creating(function ($field) {
            // Establece el estado por defecto como 'activo'
            $field->status = 'activo';
        });
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
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
