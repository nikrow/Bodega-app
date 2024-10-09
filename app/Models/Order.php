<?php

namespace App\Models;

use App\Enums\StatusType;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

class Order extends Model
{
    use HasFactory;


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
        'wharehouse_id',
        'updated_at'
    ];
    protected $casts = [
        'family' => 'array',
        'equipment' => 'array',
        'epp' => 'array',
        'parcels' => 'array',
        'status' => StatusType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($order) {

            $order->user_id = Auth::id();
            $order->field_id = Filament::getTenant()->id;
            $order->status = StatusType::PENDIENTE;
        });
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $tenant = Filament::getTenant();
            if ($tenant) {
                $order->field_id = $tenant->id;
            }
            $order->orderNumber = self::generateUniqueOrderNumber($order->field_id);
        });
    }
    public static function generateUniqueOrderNumber($fieldId)
    {
        return DB::transaction(function () use ($fieldId) {
            // Obtener el último número de orden para el campo específico
            $lastOrder = Order::where('field_id', $fieldId)
                ->lockForUpdate() // Bloqueo para evitar condiciones de carrera
                ->max('orderNumber');

            return $lastOrder ? $lastOrder + 1 : 1;
        });
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
    public function wharehouse()
    {
        return $this->belongsTo(Wharehouse::class, 'wharehouse_id');
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
    public function orderAplications()
    {
        return $this->hasMany(OrderAplication::class, 'order_id', 'id');
    }

}
