<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Services\WiseconnService;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasAvatar;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Field extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'created_by',
        'api_key',
        'wiseconn_farm_id',
    ];
    // Encriptar la clave API al guardarla
    public function setApiKeyAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_key'] = null; // Asegura que no se guarde un valor vacío
        }
    }

    // Desencriptar la clave API al recuperarla
    public function getApiKeyAttribute($value)
    {
        if (is_null($value)) {
            return null; // Retorna null si el valor no está definido
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Maneja casos donde el valor no es un payload encriptado válido
            return null; // O registra el error si es necesario
        }
    }


    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }
    protected static function booted()
    {
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->slug = Str::slug($field->name);
        });
        static::saved(function ($field) {

            if ($field->wasRecentlyCreated || $field->isDirty('wiseconn_farm_id') || $field->isDirty('api_key')) {
                
                Log::info("Evento 'saved' detectado para Field ID: {$field->id}. Lanzando syncFarmData.");
                if ($field->wiseconn_farm_id && $field->api_key) {
                    app(WiseconnService::class)->syncFarmData($field);
                }
            }
        });
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function crop()
    {
        return $this->HasMany(Crop::class, 'crop_id');
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
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function applicators()
    {
        return $this->hasMany(Applicator::class);
    }
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
    public function parcels()
    {
        return $this->hasMany(Parcel::class);
    }
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function climates()
    {
        return $this->hasMany(Climate::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'field_user', 'field_id', 'user_id');
    }
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    public function machineries()
    {
        return $this->hasMany(Machinery::class);
    }
    public function tractors()
    {
        return $this->hasMany(Tractor::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    public function zones()
    {
        return $this->hasMany(Zone::class, 'field_id');
    }
    public function fertilizations()
    {
        return $this->hasMany(Fertilization::class, 'field_id');
    }
    public function irrigation()
    {
        return $this->hasMany(Irrigation::class, 'field_id');
    }

}

