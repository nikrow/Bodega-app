<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FertilizationProduct extends Model
{
    protected $table = 'fertilization_product';

    protected $fillable = [
        'fertilization_id',
        'product_id',
        'created_by',
        'updated_by',
    ];
    
    protected $casts = [
        'fertilization_id' => 'integer',
        'product_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];
    public function fertilization() 
    {
        return $this->belongsTo(Fertilization::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
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
