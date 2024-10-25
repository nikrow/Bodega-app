<?php

namespace App\Models;

use App\Enums\StatusType;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'orderNumber',
        'status',
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
        'applicators',
        'done'
    ];
    protected $casts = [
        'family' => 'array',
        'equipment' => 'array',
        'epp' => 'array',
        'parcels' => 'array',
        'status' => StatusType::class,
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
            $order->status = StatusType::PENDIENTE;
        });
        static::updating(function ($order) {
            $order->updated_by = Auth::id();

        });
    }
    protected static function boot()
    {
        parent::boot();
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
    public function ApplicationUsage()
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
        return $this->belongsToMany(Parcel::class, 'order_parcels', 'order_id', 'parcel_id');
    }
    public function orderApplications()
    {
        return $this->hasMany(OrderApplication::class, 'order_id', 'id');
    }
    public function orderApplicators()
    {
        return $this->hasMany(OrderApplicator::class);
    }
}
