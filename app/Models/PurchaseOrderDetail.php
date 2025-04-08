<?php

namespace App\Models;

use App\Enums\StatusType;
use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderDetail extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'purchase_order_id',
        'field_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'status',
        'observation',
    ];

    protected $casts = [
        'status' => StatusType::class,
        'price' => 'decimal:2'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->field_id = Filament::getTenant()->id;
            $order->status = StatusType::PENDIENTE;
            $order->total = $order->quantity * $order->price;
        });
        static::created(function ($order) {
            $product = Product::find($order->product_id);
            $product->price = $order->price;
            $product->save();
        });
        
    }
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
}
