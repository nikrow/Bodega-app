<?php

namespace App\Models;

use Filament\Facades\Filament;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model implements Auditable
{
    use HasFactory;
    use LogsActivity;
    use \OwenIt\Auditing\Auditable;
    
    protected $fillable = [
        'crop_id',
        'field_id',
        'machinery_id',
        'tractor_id',
        'operator_id',
        'work_id',
        'date',
        'initial_hourometer', // Nuevo campo para el horómetro inicial
        'hourometer',        // Horómetro final
        'hours',             // Horas trabajadas
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
        'approved' => 'boolean',
        'initial_hourometer' => 'decimal:2',
        'hourometer' => 'decimal:2',
        'hours' => 'decimal:2',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected static function booted()
    {
        
        static::creating(function ($report) {
            $report->field_id = Filament::getTenant()->id;
            $report->operator_id = Auth::id();
            $report->created_by = Auth::id();
            $report->updated_by = Auth::id();
            static::setInitialHourometer($report); 
            static::calculateHours($report);
        });

        
        static::created(function ($report) {
            static::updateTractorData($report);
        });

        
        static::updating(function ($report) {
            $report->updated_by = Auth::id();
            static::calculateHours($report); 
            static::updateTractorData($report);
        });
    }

    // Establecer el horómetro inicial desde el tractor
    protected static function setInitialHourometer($report)
    {
        if ($report->tractor_id && !$report->initial_hourometer) {
            $tractor = Tractor::find($report->tractor_id);
            $report->initial_hourometer = $tractor->hourometer ?? 0;
        }
    }

    // Calcular las horas trabajadas
    protected static function calculateHours($report)
    {
        if ($report->hourometer && $report->initial_hourometer) {
            $report->hours = $report->hourometer - $report->initial_hourometer;
        } else {
            $report->hours = 0; 
        }
    }

    protected static function updateTractorData($report)
    {
        if ($report->tractor_id && $report->hourometer) {
            $tractor = Tractor::find($report->tractor_id);
            if ($tractor) {
                $tractor->old_hourometer = $tractor->hourometer;
                $tractor->hourometer = $report->hourometer;
                $tractor->last_hourometer_date = $report->date;
                $tractor->report_last_hourometer_id = $report->id;
                $tractor->save();
            }
        }
    }
    public function generateConsolidatedReport()
    {
        if (!$this->approved) {
            return;
        }

        $periodStart = $this->date->startOfMonth();
        $periodEnd = $this->date->endOfMonth();

        ConsolidatedReport::create([
            'tractor_id' => $this->tractor_id,
            'machinery_id' => null,
            'work_id' => $this->work_id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'tractor_hours' => $this->hours,
            'tractor_total' => $this->tractor_total,
            'machinery_hours' => 0,
            'machinery_total' => 0,
            'created_by' => Auth::id(),
            'generated_at' => now(),
        ]);
        if ($this->machinery_id) {
            ConsolidatedReport::create([
                'tractor_id' => $this->tractor_id,
                'machinery_id' => $this->machinery_id,
                'work_id' => $this->work_id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'tractor_hours' => 0,
                'tractor_total' => 0,
                'machinery_hours' => $this->hours,
                'machinery_total' => $this->machinery_total,
                'created_by' => Auth::id(),
                'generated_at' => now(),
            ]);
        }
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
        return $this->belongsTo(User::class, 'operator_id');
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

    // Accesores para totales calculados
    public function getTractorTotalAttribute()
    {
        $tractor = $this->tractor;
        return $tractor ? $this->hours * $tractor->price : 0;
    }

    public function getMachineryTotalAttribute()
    {
        $machinery = $this->machinery;
        return $machinery ? $this->hours * $machinery->price : 0;
    }
}