<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrderParcel extends Model
{
    use HasFactory;
    protected $table = 'order_parcels';
    protected $fillable = [
        'order_id',
        'field_id',
        'parcel_id',
        'created_by',
        'updated_by',
    ];
    protected static function booted()
    {
        static::creating(function ($orderParcel) {
            $orderParcel->created_by = Auth::id();
            $orderParcel->updated_by = Auth::id();

            // Verificar si hay un tenant cargado antes de asignar `field_id`
            if (Filament::getTenant()) {
                $orderParcel->field_id = Filament::getTenant()->id;
            }
        });
        static::updating(function ($order) {
            $order->updated_by = Auth::id();
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
    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function orderAplications()
    {
        return $this->hasMany(OrderAplication::class, 'order_id', 'order_id');
    }


}
