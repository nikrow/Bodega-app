<?php

namespace App\Models;

use App\Enums\WorkType;
use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLog extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use \Spatie\Activitylog\Traits\LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'date',
        'task_id',
        'contractor_id',
        'responsible_id',
        'crop_id',
        'field_id',
        'parcel_id',        
        'people_count',
        'quantity',
        'unit_type',
        'by_jornada',
        'by_unit',
        'notes',
        'is_completed',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'date' => 'date',
        'by_jornada' => 'boolean',
        'by_unit' => 'boolean',
        'is_completed' => 'boolean',
        'quantity' => 'decimal:3',
        'people_count' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected static function booted()
    {
        static::creating(function ($workLog) {
            $workLog->created_by = Auth::id();
            $workLog->updated_by = Auth::id();
            $workLog->field_id = Filament::getTenant()->id;
        });

        static::updating(function ($workLog) {
            $workLog->updated_by = Auth::id();
        });
        static::saving(function (self $log) {
            // Derivar flags desde la Task
            if ($log->task && $log->task->work_type instanceof WorkType) {
                $log->by_jornada = $log->task->work_type === WorkType::JORNADA;
                $log->by_unit    = $log->task->work_type === WorkType::UNITARIA;

                // Si es unitaria, fijar unit_type desde la task (si no viene)
                if ($log->by_unit && empty($log->unit_type)) {
                    $log->unit_type = $log->task->unit_type;
                }
                // Si es jornada, limpiar unit fields
                if ($log->by_jornada) {
                    $log->unit_type = null;
                    // quantity opcional; si la usas como “jornadas”, podrías setear 1 por defecto
                }
            }

            // Validaciones de backend (además de FormRequest):
            if ($log->by_jornada && empty($log->people_count)) {
                throw new \InvalidArgumentException('Debe indicar la cantidad de personas para faenas por jornada.');
            }
            if ($log->by_unit && (empty($log->quantity) || empty($log->unit_type))) {
                throw new \InvalidArgumentException('Debe indicar cantidad y unidad para faenas unitarias.');
            }
        });
    }
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
    public function contractor()
    {
        return $this->belongsTo(Contractor::class);     
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
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
