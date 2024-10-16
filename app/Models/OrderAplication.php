<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAplication extends Model
{
    use HasFactory;

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

    protected static function booted()
    {
        static::creating(function ($application) {
            $application->created_by = auth()->id();
            $application->updated_by = auth()->id();
            $application->field_id = Filament::getTenant()->id;
            $order = $application->order;
            if ($order->status === 'Pendiente') {
                $order->update(['status' => 'En Proceso']);
            }
        });

        static::updating(function ($application) {
            $application->updated_by = auth()->id();
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

}
