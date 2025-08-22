<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Contractor extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use \Spatie\Activitylog\Traits\LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'rut',
        'phone',
        'email',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($contractor) {
            $contractor->created_by = Auth::id();
            $contractor->updated_by = Auth::id();
        });

        static::updating(function ($contractor) {
            $contractor->updated_by = Auth::id();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
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
    