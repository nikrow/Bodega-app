<?php

namespace App\Models;

use App\Models\Tractor;
use App\Models\ConsolidatedReport;
use Filament\Facades\Filament;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'initial_hourometer',
        'hourometer',
        'hours',
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
        return LogOptions::defaults()->logFillable();
    }

    public function setHourometerAttribute($value)
    {
        $this->attributes['hourometer'] = str_replace(',', '.', $value);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($report) {
            $tractor = Tractor::where('report_last_hourometer_id', $report->id)->first();
            if ($tractor) {
                $previousReport = Report::where('tractor_id', $tractor->id)
                    ->where('id', '<', $report->id)
                    ->orderBy('id', 'desc')
                    ->first();
                $tractor->hourometer = $previousReport ? $previousReport->hourometer : 0;
                $tractor->report_last_hourometer_id = $previousReport ? $previousReport->id : null;
                $tractor->save();
            }
        });

        static::deleted(function ($report) {
            // Encontrar el reporte anterior al eliminado
            $previousReport = Report::where('tractor_id', $report->tractor_id)
                ->where('id', '<', $report->id)
                ->orderBy('id', 'desc')
                ->first();
        
            // Determinar el valor inicial del horómetro para los reportes posteriores
            $initialHourometer = $previousReport ? $previousReport->hourometer : Tractor::find($report->tractor_id)->hourometer ?? 0;
        
            // Obtener los reportes posteriores ordenados por id
            $subsequentReports = Report::where('tractor_id', $report->tractor_id)
                ->where('id', '>', $report->id)
                ->orderBy('id')
                ->get();
        
            // Actualizar cada reporte posterior en cascada
            foreach ($subsequentReports as $subsequentReport) {
                $subsequentReport->initial_hourometer = $initialHourometer;
                $subsequentReport->hours = $subsequentReport->hourometer - $initialHourometer;
                $subsequentReport->save();
                $initialHourometer = $subsequentReport->hourometer;
            }
        });
    }

    protected static function booted()
    {
        static::creating(function ($report) {
            $report->field_id = Filament::getTenant()->id;
            $report->created_by = Auth::id();
            $report->updated_by = Auth::id();
            static::setInitialHourometer($report);
            static::calculateHours($report);
        });

        static::updating(function ($report) {
            $report->updated_by = Auth::id();
            static::calculateHours($report);
        });
    }

    protected static function setInitialHourometer($report)
    {
        if ($report->tractor_id && !$report->initial_hourometer) {
            $lastReport = Report::where('tractor_id', $report->tractor_id)
                ->orderBy('id', 'desc')
                ->first();
            $report->initial_hourometer = $lastReport ? $lastReport->hourometer : Tractor::find($report->tractor_id)->hourometer ?? 0;
        }
    }

    protected static function calculateHours($report)
    {
        if ($report->hourometer && $report->initial_hourometer) {
            $report->hours = $report->hourometer - $report->initial_hourometer;
        } else {
            $report->hours = 0;
        }
    }

    public function generateConsolidatedReport()
    {
        if (!$this->approved) {
            return;
        }

        $periodStart = $this->date->startOfMonth();
        $periodEnd = $this->date->endOfMonth();

        ConsolidatedReport::updateOrCreate(
            [
                'report_id' => $this->id,
                'tractor_id' => $this->tractor_id,
                'machinery_id' => null,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'tractor_hours' => $this->hours,
                'tractor_total' => $this->tractor_total,
                'machinery_hours' => 0,
                'machinery_total' => 0,
                'created_by' => Auth::id(),
                'generated_at' => now(),
            ]
        );

        if ($this->machinery_id) {
            ConsolidatedReport::updateOrCreate(
                [
                    'report_id' => $this->id,
                    'tractor_id' => $this->tractor_id,
                    'machinery_id' => $this->machinery_id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ],
                [
                    'tractor_hours' => 0,
                    'tractor_total' => 0,
                    'machinery_hours' => $this->hours,
                    'machinery_total' => $this->machinery_total,
                    'created_by' => Auth::id(),
                    'generated_at' => now(),
                ]
            );
        }

        // Actualizar el tractor solo al aprobar el reporte
        $tractor = $this->tractor;
        if ($tractor) {
            $tractor->old_hourometer = $tractor->hourometer;
            $tractor->hourometer = $this->hourometer;
            $tractor->last_hourometer_date = $this->date;
            $tractor->report_last_hourometer_id = $this->id;
            $tractor->save();
        }
    }
    
    public function operator()
    {
        return $this->belongsTo(User::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function tractor()
    {
        return $this->belongsTo(Tractor::class);
    }

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
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