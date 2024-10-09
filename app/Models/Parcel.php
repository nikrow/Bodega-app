<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class Parcel extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'field_id',
        'crop_id',
        'planting_year',
        'planting-schema',
        'plants',
        'surface',
        'created_by',
        'updated_by',
        'slug',
    ];
    protected static function booted()
    {
        static::creating(function ($parcel) {
            // Asignar `created_by` y `updated_by` al usuario autenticado
            $parcel->created_by = Auth::id();
            $parcel->updated_by = Auth::id();

            // Asignar el `field_id` del tenant actual si está disponible
            if (Filament::getTenant()) {
                $parcel->field_id = Filament::getTenant()->id;
            }

            // Generar el slug a partir del nombre
            $parcel->slug = Str::slug($parcel->name);
        });

        static::updating(function ($field) {
            $field->updated_by = Auth::id();
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

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function order()
    {
        return $this->belongsToMany(Order::class, 'order_parcels', 'parcel_id', 'order_id')
            ->withTimestamps();
    }
    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id');
    }




}
