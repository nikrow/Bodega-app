<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductPackage extends Pivot
{
    protected $table = 'product_packages';

    protected $fillable = [
        'product_id',
        'package_id',
    ];
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Validar que la combinación product_id y package_id sea única (si es necesario)
            $exists = self::where('product_id', $model->product_id)
                            ->where('package_id', $model->package_id)
                            ->where('id', '!=', $model->id)
                            ->exists();

            if ($exists) {
                throw new \Exception('Esta combinación de Producto y Envase ya existe.');
            }
        });
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación inversa con Package.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
