<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fertilization extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'parcel_id',
        'field_id',
        'irrigation_id',
        'product_id',
        'fertilizer_mapping_id',
        'product_price',
        'total_cost',
        'application_method',
        'surface',
        'quantity_solution',
        'dilution_factor',
        'quantity_product',
        'date',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'quantity_solution' => 'decimal:2',
        'dilution_factor' => 'decimal:2',
        'quantity_product' => 'decimal:2',
        'date' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected static function booted()
    {
        static::creating(function ($fertilization) {
            $fertilization->created_by = Auth::id();
            $fertilization->updated_by = Auth::id();
            
            if ($fertilization->irrigation_id) {
                $irrigation = Irrigation::find($fertilization->irrigation_id);
                if ($irrigation) {
                    $fertilization->parcel_id = $irrigation->parcel_id;
                    $fertilization->field_id = $irrigation->field_id;
                }
            }
        });

        static::updating(function ($fertilization) {
            $fertilization->updated_by = Auth::id();
        });
    }
    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
    public function fertilizerMapping()
    {
        return $this->belongsTo(FertilizerMapping::class, 'fertilizer_mapping_id');
    }
    public function irrigation()
    {
        return $this->belongsTo(Irrigation::class, 'irrigation_id');
    }
}