<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Zone extends Model
{

    protected $fillable = [
        'field_id',
        'wiseconn_zone_id',
        'name',
        'description',
        'latitude',
        'longitude',
        'type',
        'is_historical_initialized',
    ];

    protected $casts = [
        'type' => 'array',
        'is_historical_initialized' => 'boolean',
    ];

    /**
     * Relación con el campo (Field).
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Relación con el resumen de la zona (ZoneSummary).
     */
    public function summary(): HasOne
    {
        return $this->hasOne(ZoneSummary::class);
    }
}