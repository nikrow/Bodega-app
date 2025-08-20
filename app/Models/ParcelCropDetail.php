<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable; 
use Spatie\Activitylog\LogOptions;      
use Spatie\Activitylog\Traits\LogsActivity; 

class ParcelCropDetail extends Model implements Auditable
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity; 
    use \OwenIt\Auditing\Auditable;

    protected $table = 'parcel_crop_details'; 

    protected $fillable = [
        'subsector',
        'parcel_id',
        'crop_id',
        'variety_id',
        'rootstock_id',
        'surface',
        'planting_scheme_id',
        'irrigation_system',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($parcelCropDetail) {
            // Obtenemos el crop_id del modelo Parcel asociado
            if ($parcelCropDetail->parcel) {
                $parcelCropDetail->crop_id = $parcelCropDetail->parcel->crop_id;
            }
        });
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id');
    }

    public function variety()
    {
        return $this->belongsTo(Variety::class, 'variety_id');
    }

    public function rootstock()
    {
        return $this->belongsTo(Rootstock::class, 'rootstock_id');
    }
    public function plantingScheme()
    {
        return $this->belongsTo(PlantingScheme::class, 'planting_scheme_id');
    }

}