<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use OwenIt\Auditing\Contracts\Auditable;

class OrderApplicationUsage extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'order_application_usages';
    protected $fillable = [
        'field_id',
        'order_id',
        'orderNumber',
        'parcel_id',
        'product_id',
        'price',
        'total_cost',
        'liters_applied',
        'dose_per_100l',
        'product_usage',
        'order_application_id',
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
    protected static function booted()
    {
        static::creating(function ($usage) {
            $usage->calculatePriceAndTotalCost();
        });

        static::updating(function ($usage) {
            $usage->calculatePriceAndTotalCost();
        });
    }

    public function calculatePriceAndTotalCost()
    {
        // Obtener el precio del producto
        $product = Product::find($this->product_id);
        $price = $product->price ?? 0;

        $this->price = $price;
        $this->total_cost = $price * $this->product_usage;
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
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

    public function orderApplication()
    {
        return $this->belongsTo(OrderApplication::class, 'order_application_id');
    }

    // Relación con Order a través de OrderApplication
    public function order()
    {
        return $this->hasOneThrough(Order::class, OrderApplication::class, 'id', 'id', 'order_application_id', 'order_id');
    }

    public function getApplicatorsDetailsAttribute()
    {
        if ($this->orderApplication && $this->orderApplication->applicators) {
            return $this->orderApplication->applicators->map(function ($applicator) {
                $details = [];

                // Nombre del aplicador
                $details[] = $applicator->name ?? 'Nombre desconocido';

                // Tractor del aplicador
                if (!empty($applicator->tractor)) {
                    $details[] = $applicator->tractor;
                } else {
                    $details[] = 'Sin tractor';
                }

                // Equipamiento del aplicador
                if (!empty($applicator->equipment)) {
                    // Si 'equipment' es un array, lo convertimos a cadena
                    $equipment = is_array($applicator->equipment) ? implode(', ', $applicator->equipment) : $applicator->equipment;
                    $details[] = $equipment;
                } else {
                    $details[] = 'Sin equipamiento';
                }

                // Unir los detalles del aplicador con comas
                return implode(', ', $details);
            })->implode(' - ');
        }
        return null;
    }


}
