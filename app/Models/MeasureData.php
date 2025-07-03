<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasureData extends Model
{
    protected $fillable = [
        'measure_id',
        'time',
        'value',
    ];

    protected $casts = [
        'time' => 'datetime',
        'value' => 'decimal:2',
    ];

    public function measure()
    {
        return $this->belongsTo(Measure::class);
    }
}