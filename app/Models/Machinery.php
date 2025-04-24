<?php

namespace App\Models;

use App\Enums\ProviderType;
use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Machinery extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'name',
        'field_id',
        'provider',
        'SapCode',
        'price',
        'qrcode',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'provider' => ProviderType::class,
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($machinery) {
            $machinery->field_id = Filament::getTenant()->id;
            $machinery->created_by = Auth::id();
            $machinery->updated_by = Auth::id();
            $machinery->qrcode = uniqid();
        });

        static::updating(function ($machinery) {
            $machinery->updated_by = Auth::id();
        });
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    public function works()
    {
        return $this->belongsToMany(Work::class, 'machinery_work', 'machinery_id', 'work_id');
    }

    public function operators()
    {
        return $this->belongsToMany(User::class, 'user_machinery', 'machinery_id', 'user_id');
    }
}
