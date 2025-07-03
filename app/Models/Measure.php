<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measure extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}