<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedEvent extends Model
{
    protected $fillable = [
        'tenant', 
        'description', 
        'batch_id',
        'date_time', 
        'duration', 
        'quantity_m3', 
        'fertilizers', 
        'status', 
        'error_message',
        
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'fertilizers' => 'array',
        'quantity_m3' => 'decimal:2',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'tenant', 'id');
    }
    public function batch()
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id', 'id');
    }
}