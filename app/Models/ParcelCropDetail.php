<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable; 
use Spatie\Activitylog\LogOptions;      
use Spatie\Activitylog\Traits\LogsActivity; 

class ParcelCropDetail extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity; 
    use \OwenIt\Auditing\Auditable;

    protected $table = 'parcel_crop_details'; 

    protected $fillable = [
        'parcel_id',
        'crop_id',
        'variety_id',
        'rootstock_id',
        'surface',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
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
}