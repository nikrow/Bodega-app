<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Tractor extends Model
{
    protected $fillable = [
        'name',
        'field_id',
        'provider',
        'SapCode',
        'price',
        'qrcode',
        'created_by',
        'hourometer',
        'old_hourometer',
        'last_hourometer_date',
        'report_last_hourometer_id',
        'updated_by',
    ];
    protected $casts = [
        'price' => 'decimal:2',
        
    ];
    protected static function booted()
    {
        static::creating(function ($tractor) {
            $tractor->field_id = Filament::getTenant()->id;
            $tractor->created_by = Auth::id();
            $tractor->updated_by = Auth::id();
            $tractor->qrcode = uniqid();
        });

        static::updating(function ($tractor) {
            $tractor->updated_by = Auth::id();
        });
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function operators()
    {
        return $this->belongsToMany(User::class, 'tractor_user', 'tractor_id', 'user_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
