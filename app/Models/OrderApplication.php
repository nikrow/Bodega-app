<?php

namespace App\Models;

use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderApplication extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'order_id',
        'field_id',
        'parcel_id',
        'liter',
        'wetting',
        'wind_speed',
        'temperature',
        'moisture',
        'tractor_id',
        'applicator_id',
        'application_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'surface',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($application) {
            $application->created_by = Auth::id();
            $application->updated_by = Auth::id();
            $application->field_id = Filament::getTenant()->id;
            $order = $application->order;
            if ($order->status === 'Pendiente') {
                $order->update(['status' => 'En Proceso']);
            }
        });

        static::updating(function ($application) {
            $application->updated_by = Auth::id();
        });
    }

    public function getApplicationPercentageAttribute()
    {
        $parcelSurface = $this->parcel->surface ?? 0;
        $surfaceApplied = $this->surface ?? 0;

        if ($parcelSurface > 0) {
            $percentage = ($surfaceApplied / $parcelSurface) * 100;
            return round($percentage, 2);
        }

        return null;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }
    public function orderParcel()
    {
        return $this->belongsTo(OrderParcel::class, 'parcel_id');
    }
    public function climate()
    {
        return $this->belongsTo(Climate::class, 'climate_id');
    }
    public function applicators()
    {
        return $this->belongsToMany(Applicator::class, 'order_application_applicator', 'order_application_id', 'applicator_id')
            ->where('field_id', Filament::getTenant()->id);
    }
    public function orderApplicationUsage()
    {
        return $this->hasMany(OrderApplicationUsage::class, 'order_application_id');
    }
    public function applicationUsage()
    {
        return $this->hasMany(OrderApplicationUsage::class, 'order_application_id');
    }

}
