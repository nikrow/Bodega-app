<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Parcel extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'field_id',
        'crop_id',
        'planting_year',
        'plants',
        'surface',
        'created_by',
        'updated_by',
        'slug',
        'is_active',
        'user_id',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
        'sdp',
        'tank',
        'irrigation_system',
        'planting_scheme_id',
        'planting_scheme_custom',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected static function booted()
    {
        static::creating(function ($parcel) {
            $parcel->user_id = Auth::id();
            $parcel->created_by = Auth::id();
            $parcel->updated_by = Auth::id();
            $parcel->is_active = true;
            $originalSlug = Str::slug($parcel->name);
            $slug = $originalSlug;
            $count = 1;

            while (Parcel::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-{$count}";
                $count++;
            }

            $parcel->slug = $slug;
            if ($parcel->planting_scheme_id === null && !empty($parcel->planting_scheme_custom)) {
                $newScheme = PlantingScheme::firstOrCreate(['scheme' => trim($parcel->planting_scheme_custom)]);
                $parcel->planting_scheme_id = $newScheme->id;
            }
        });

        static::updating(function ($parcel) {
            if ($parcel->is_active) {
                $parcel->updated_by = Auth::id();
            }
            if ($parcel->planting_scheme_id === null && !empty($parcel->planting_scheme_custom)) {
                $newScheme = PlantingScheme::firstOrCreate(['scheme' => trim($parcel->planting_scheme_custom)]);
                $parcel->planting_scheme_id = $newScheme->id;
            }
        });
    }

    protected $casts = [
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

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

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function deactivatedByName()
    {
        return $this->belongsTo(User::class, 'deactivated_by')->select(['id', 'name']);
    }

    public function applicationUsages()
    {
        return $this->hasMany(OrderApplicationUsage::class, 'parcel_id');
    }

    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }

    public function parcelCropDetails()
    {
        return $this->hasMany(ParcelCropDetail::class, 'parcel_id');
    }

    public function plantingScheme()
    {
        return $this->belongsTo(PlantingScheme::class, 'planting_scheme_id');
    }
    public function programParcels()
    {
        return $this->hasMany(ProgramParcel::class, 'parcel_id');
    }
}