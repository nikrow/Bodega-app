<?php

namespace App\Models;

use App\Enums\WorkType;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use \Spatie\Activitylog\Traits\LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'crop_id',
        'work_type',
        'unit_type',
        'plant_control',
        'is_active',
        'created_by',
        'updated_by',

    ];
    protected $casts = [
        'work_type' => WorkType::class,
        'plant_control' => 'boolean',
        'is_active' => 'boolean',
    ];
    protected static function booted()
    {
        static::creating(function ($task) {
            $task->created_by = Auth::id();
            $task->updated_by = Auth::id();
        });

        static::updating(function ($task) {
            $task->updated_by = Auth::id();
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }
    public function workLogs()
    {
        return $this->hasMany(WorkLog::class);
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
