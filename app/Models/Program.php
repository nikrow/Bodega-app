<?php

namespace App\Models;

use id;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'field_id',
        'crop_id',
        'description',
        'created_by',
        'updated_by',
        'is_active',
        'slug',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->field_id = Filament::getTenant()->id;
            $model->slug = Str::slug($model->name) . '-' . uniqid();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        static::saving(function ($model) {
            if ($model->exists && $model->getOriginal('is_active') === false) {
            // Se permite la modificación solo si el campo que cambia es 'is_active' (para poder reabrirlo).
            if (!$model->isDirty('is_active')) {
                throw new \Exception('No se puede modificar un programa que está cerrado.');
            }
            }
            if ($model->start_date > $model->end_date) {
                throw new \Exception('La fecha de inicio debe ser anterior a la fecha de fin.');
            }
    });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
    public function fertilizers()
    {
        return $this->hasMany(ProgramFertilizer::class);
    }

    public function parcels()
    {
        return $this->belongsToMany(Parcel::class, 'program_parcels', 'program_id', 'parcel_id')
            ->withPivot('field_id', 'area', 'fertilizer_mapping_id', 'fertilizer_amount', 'created_by', 'updated_by')
            ->using(ProgramParcel::class); 
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
    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id');
    }
}
