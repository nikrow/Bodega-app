<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneSummary extends Model
{
    protected $fillable = [
        'field_id',
        'zone_id',
        'current_temperature',
        'current_temperature_time',
        'min_temperature_daily',
        'min_temperature_time',
        'max_temperature_daily',
        'max_temperature_time',
        'daily_rain',
        'daily_rain_time',
        'current_humidity',
        'current_humidity_time',
        'chill_hours_accumulated',
        'chill_hours_accumulated_time',
        'chill_hours_daily',
        'chill_hours_daily_time',
    ];

    protected $casts = [
        'current_temperature_time' => 'datetime',
        'min_temperature_time' => 'datetime',
        'max_temperature_time' => 'datetime',
        'daily_rain_time' => 'datetime',
        'current_humidity_time' => 'datetime',
        'chill_hours_accumulated_time' => 'datetime',
        'chill_hours_daily_time' => 'datetime',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}