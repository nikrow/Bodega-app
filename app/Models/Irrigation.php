<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Irrigation extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'parcel_id',
        'field_id',
        'date',
        'surface',
        'duration',
        'quantity_m3',
        'type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $casts = [
        'duration' => 'integer',
        'surface' => 'decimal:2',
        'date' => 'datetime',
        'quantity_m3' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected static function booted()
    {
        static::creating(function ($irrigation) {
            $irrigation->created_by = Auth::id();
            $irrigation->updated_by = Auth::id();
        });

        static::updating(function ($irrigation) {
            $irrigation->updated_by = Auth::id();
        });
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
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
    public function fertilization()
    {
        return $this->hasMany(Fertilization::class, 'irrigation_id');
    }
}