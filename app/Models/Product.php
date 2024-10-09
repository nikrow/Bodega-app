<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;

class Product extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'product_name',
        'active_ingredients',
        'SAP_code',
        'SAP_family',
        'family',
        'price',
        'waiting_time',
        'reentry',
        'created_by',
        'updated_by',
        'unit_measure',
        'field_id',
        'slug',
        'dosis_min',
        'dosis_max',
    ];
    protected static function booted()
    {
        static::creating(function ($field) {

            $field->created_by = Auth::id();
            $field->updated_by = Auth::id();
            $field->slug = Str::slug($field->product_name);
        });
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

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

}
