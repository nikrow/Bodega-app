<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Applicator extends Model
{
    use LogsActivity;
    use HasFactory;
    protected $table = 'applicators';
    protected $fillable = [
        'field_id',
        'name',
        'rut',
        'tractor',
        'equipment',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($aplicator) {
            $aplicator->created_by = Auth::id();
            $aplicator->updated_by = Auth::id();
        });
        static::updating(function ($aplicator) {
            $aplicator->updated_by = Auth::id();
        });
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_application_applicator', 'applicator_id', 'order_application_id')
            ->withTimestamps();
    }


}
