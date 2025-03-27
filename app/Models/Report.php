<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'crop_id',
        'field_id',
        'machinery_id',
        'tractor_id',
        'operator_id',
        'work_id',
        'date',
        'hourometer',
        'created_by',
        'updated_by',
        'observations',
        'approved',
        'approved_by',
        'approved_at',
    ];
    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'aproved' => 'boolean',
        'hourometer' => 'decimal:2',
    ];
    protected static function booted()
    {
        static::creating(function ($report) {
            $report->field_id = Filament::getTenant()->id;
            $report->operator_id = Auth::id();
            $report->created_by = Auth::id();
            $report->updated_by = Auth::id();
        });
        static::updating(function ($report) {
            $report->updated_by = Auth::id();
        });
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }
    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
    public function work()
    {
        return $this->belongsTo(Work::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
}
