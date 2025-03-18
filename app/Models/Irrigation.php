<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Irrigation extends Model
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'parcel_id',
        'field_id',
        'date',
        'duration',
        'volume',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function fertilization()
    {
        return $this->hasOne(Fertilization::class);
    }
}
