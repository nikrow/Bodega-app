<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FertilizerMapping extends Model
{
    protected $table = 'fertilizer_mappings';

    protected $fillable = [
        'excel_column_name',
        'product_id',
        'dilution_factor',
        'fertilizer_name',
    ];

    protected $casts = [    
        'product_id' => 'integer',
        'dilution_factor' => 'decimal:2',
        'fertilizer_name' => 'string',
    ];

    public function fertilization()
    {
        return $this->belongsTo(Fertilization::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
