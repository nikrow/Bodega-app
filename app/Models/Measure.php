<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measure extends Model
{
    protected $fillable = [
        'zone_id',
        'measure_id',
        'name',
        'unit',
        'value',
        'time',
        'sensor_type',
    ];

    protected $casts = [
        'time' => 'datetime',
        'value' => 'decimal:2',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

}