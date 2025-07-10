<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Rootstock extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'rootstocks';

    protected $fillable = [
        'name',
        'crop_id', // Relación con el cultivo al que pertenece el portainjerto
        'description', // Descripción opcional del portainjerto
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crop_id'); // Relación con el modelo Crop
    }
}