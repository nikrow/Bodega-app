<?php

namespace App\Models;

use DateTimeInterface;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderApplicationUsage extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $table = 'order_application_usages';
    protected $fillable = [
        'field_id',
        'order_id',
        'orderNumber',
        'parcel_id',
        'product_id',
        'liters_applied',
        'dose_per_100l',
        'product_usage',
        'created_at',
        'updated_at',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            $model->field_id = Filament::getTenant()->id;
        });

    }

    // Método para calcular la cantidad utilizada


    // Relación con OrderApplication
    public function orderApplication()
    {
        return $this->belongsTo(OrderApplication::class, 'application_id');
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
