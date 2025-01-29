<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Climate extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'climates';
    protected $fillable = [
        'field_id',
        'wind',
        'temperature',
        'humidity',
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
        static::creating(function ($climate) {
            $climate->created_by = auth()->id();
            $climate->field_id = Filament::getTenant()->id;
            $climate->updated_by = auth()->id();
        });
        static::updating(function ($climate) {
            $climate->updated_by = auth()->id();
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
        return $this->hasMany(Order::class);
    }
    public function audit()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
}
