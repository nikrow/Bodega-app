<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'type' => 'array',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function measures()
    {
        return $this->hasMany(Measure::class);
    }
}
