<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Crop extends Model implements Auditable
{

    
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'crops';
    protected $fillable = [
        'especie',
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
        static::creating(function ($crop) {

            $crop->created_by = Auth::id();
            $crop->updated_by = Auth::id();
        });
        static::updating(function ($crop) {
            $crop->updated_by = Auth::id();
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
    public function varieties()
    {
        return $this->hasMany(Variety::class, 'crop_id'); // Relación con el modelo Variety
    }
    public function rootstocks()
    {
        return $this->hasMany(Rootstock::class, 'crop_id'); // Relación con el modelo Rootstock
    }
    public function parcels()
    {
        return $this->hasMany(Parcel::class, 'crop_id');
    }
    
}
