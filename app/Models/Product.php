<?php

namespace App\Models;

use App\Enums\FamilyType;
use App\Enums\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'product_name',
        'active_ingredients',
        'SAP_code',
        'SAP_family',
        'family',
        'price',
        'requires_batch_control',
        'waiting_time',
        'sag_code',
        'reentry',
        'created_by',
        'updated_by',
        'unit_measure',
        'field_id',
        'slug',
        'dosis_min',
        'dosis_max',
    ];
    protected $casts = [
        'requires_batch_control' => 'boolean',
        'family' => FamilyType::class,
        'unit_measure' => UnidadMedida::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->updated_by = Auth::id();
            $field->slug = Str::slug($field->product_name);
        });
    }
    public function setDosisMinAttribute($value)
    {
        $this->attributes['dosis_min'] = str_replace(',', '.', $value);
    }

    public function fertilizations()
    {
        return $this->belongsToMany(Fertilization::class, 'fertilization_product')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }
    /**
     * Set the dosis_max attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setDosisMaxAttribute($value)
    {
        $this->attributes['dosis_max'] = str_replace(',', '.', $value);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function audit()
    {
        return $this->morphTo(Audit::class, 'auditable_id', 'id');
    }
    public function movimientos()
    {
        return $this->hasMany(MovimientoProducto::class, 'producto_id');
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function requiresBatchControl(): bool
    {
        return $this->requires_batch_control;
    }
    public function packages()
{
    return $this->belongsToMany(Package::class, 'product_packages')
                ->using(ProductPackage::class)
                ->withTimestamps();
                
}


}
