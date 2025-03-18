<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fertilization extends Model
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'parcel_id',
        'field_id',
        'irrigation_id',
        'date',
        'product_id',
        'quantity',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    public function irrigation()
    {
        return $this->belongsTo(Irrigation::class);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Product::class, 'fertilization_product')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }

}
