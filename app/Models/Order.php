<?php

namespace App\Models;


use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'orderNumber',
        'field_id',
        'crops_id',
        'wetting',
        'equipment',
        'family',
        'epp',
        'created_at',
        'warehouse_id',
        'updated_at',
        'updated_by',
        'is_completed',
        'applicators',
        'objective',
        'observations',
        'indications',

    ];
    protected $casts = [
        'family' => 'array',
        'epp' => 'array',
        'equipment' => 'array',
        'applicators' => 'array',
        'is_completed' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($order) {

            $order->user_id = Auth::id();
            $order->updated_by = Auth::id();
            $order->orderNumber = self::generateUniqueOrderNumber();
            $order->field_id = Filament::getTenant()->id;
            $order->is_completed = false;
        });
        static::updating(function ($order) {
            if ($order->is_completed && $order->isDirty()) {
                $cambiosRelevantes = collect($order->getDirty())->except(['is_completed', 'observations']);
                if ($cambiosRelevantes->isEmpty()) {
                    return;
                }
                throw ValidationException::withMessages([
                    'is_completed' => 'No puedes modificar un movimiento que ya ha sido completado.',
                ]);

            }
        });

    }
    /**
     * Obtener el área total de las parcelas asociadas a la orden.
     *
     * @return float
     */
    public function getTotalAreaAttribute(): float
    {
        return $this->parcels()->sum('surface');
    }

    protected static function boot()
    {
        parent::boot();
    }

    public function getParcelAppliedPercentagesAttribute()
    {
        $percentages = [];

        foreach ($this->parcels as $parcel) {
            $parcelSurface = $parcel->surface ?? 0;

            // Obtener todas las aplicaciones para este cuartel en esta orden
            $totalSurfaceApplied = $this->orderApplications()
                ->where('parcel_id', $parcel->id)
                ->sum('surface');

            if ($parcelSurface > 0) {
                $percentage = ($totalSurfaceApplied / $parcelSurface) * 100;
                $percentages[$parcel->name] = round($percentage, 2);
            } else {
                $percentages[$parcel->name] = 0;
            }
        }

        return $percentages;
    }

    public function getTotalAppliedPercentageAttribute()
    {
        $totalParcelSurface = $this->parcels->sum('surface');
        $totalSurfaceApplied = $this->orderApplications->sum('surface');

        if ($totalParcelSurface > 0) {
            $percentage = ($totalSurfaceApplied / $totalParcelSurface) * 100;
            return round($percentage, 2);
        }

        return 0;
    }

    public static function generateUniqueOrderNumber()
    {
        // Obtener el último movimiento creado
        $latestOrder = self::latest('id')->lockForUpdate()->first();

        // Calcular el siguiente número incrementando el último ID
        $nextNumber = $latestOrder ? $latestOrder->id + 1 : 1;

        // Formatear la fecha en el formato deseado (Año-Mes-Día)
        $date = date('Y-m-d');

        // Devolver el número de movimiento con la fecha y el número incremental
        return $date . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        // Ejemplo: 2024-09-30-000001
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con el usuario que actualizó el movimiento
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function orderLines()
    {
        return $this->hasMany(order_line::class);
    }
    public function applicationUsage()
        {
            return $this->hasMany(OrderApplicationUsage::class);
        }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function crop()
    {
        return $this->belongsTo(Crop::class, 'crops_id');
    }
    public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }
    public function parcels()
    {
        return $this->belongsToMany(Parcel::class, 'order_parcels', 'order_id', 'parcel_id')
            ->withPivot(['field_id', 'created_by', 'updated_by'])
            ->withTimestamps();
    }
       public function orderApplications()
    {
        return $this->hasMany(OrderApplication::class, 'order_id', 'id');
    }
    public function applicators()
    {
        return $this->belongsToMany(Applicator::class, 'order_application_applicator', 'order_application_id', 'applicator_id')
            ->withTimestamps();
    }
    public function orderApplicationUsages()
    {
        return $this->hasManyThrough(OrderApplicationUsage::class, OrderApplication::class, 'order_id', 'order_application_id');
    }



}
