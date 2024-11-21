<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Warehouse extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'status',
        'field_id',
        'slug',
        'created_by',
        'updated_by',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }


    protected static function booted()
    {
        static::creating(function ($field) {
            // Asigna el ID del usuario autenticado
            $field->created_by = Auth::id();
        });

        static::creating(function ($warehouse) {
            $warehouse->field_id = Filament::getTenant()->id;
        });

        static::creating(function ($field) {
            // Genera el slug a partir del nombre
            $field->slug = Str::slug($field->name);
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
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_warehouse')
            ->withTimestamps();
    }
}
