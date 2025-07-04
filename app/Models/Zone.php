<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function measures(): HasMany
    {
        return $this->hasMany(Measure::class);
    }

    public function summary(): HasOne
    {
        return $this->hasOne(ZoneSummary::class);
    }
}