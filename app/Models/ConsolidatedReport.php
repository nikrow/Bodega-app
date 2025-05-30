<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsolidatedReport extends Model
{
    protected $fillable = [
        'tractor_id',
        'machinery_id',
        'period_start',
        'period_end',
        'tractor_hours',
        'tractor_total',
        'machinery_hours',
        'machinery_total',
        'created_by',
        'generated_at',
        'report_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'tractor_hours' => 'decimal:2',
        'tractor_total' => 'decimal:2',
        'machinery_hours' => 'decimal:2',
        'machinery_total' => 'decimal:2',
    ];

    public function save(array $options = [])
    {
        if ($this->exists) {
            return false; 
        }
        return parent::save($options);
    }
    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }
    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
    public function works()
{
    return $this->belongsToMany(Work::class, 'machinery_work', 'machinery_id', 'work_id');
}
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
