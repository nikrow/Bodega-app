<?php

namespace App\Models;

use id;
use App\Enums\Destination;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;


    protected $fillable = [
        'name',
        'description',
        'capacity',
        'weight',
        'destination',
        'created_by',
    ];

    protected $casts = [
        'destination' => Destination::class,
        'capacity' => 'decimal:2',
        'weight' => 'decimal:3',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    public static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            $package->created_by = auth()->id();
        });
    
    }
    public function products()
{
    return $this->belongsToMany(Product::class, 'product_packages')
                ->using(ProductPackage::class)
                ->withTimestamps();
}

}
