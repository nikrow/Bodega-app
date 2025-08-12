<?php

namespace App\Models;

use Filament\Facades\Filament;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramFertilizer extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    use LogsActivity;
    
    protected $fillable = [
        'program_id',
        'field_id',
        'fertilizer_mapping_id',
        'dilution_factor',
        'units_per_ha',
        'application_quantity',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dilution_factor' => 'decimal:2',
        'units_per_ha' => 'decimal:2',
        'application_quantity' => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->field_id = Filament::getTenant()->id;
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        static::saving(function ($model) {
        if ($model->dilution_factor <= 0 || $model->units_per_ha <= 0) {
            throw new \Exception('El factor de dilución y las unidades por hectárea deben ser mayores que cero.');
        }

        static::created(function ($programFertilizer) {
            $program = $programFertilizer->program;
            $existingParcels = $program->parcels; 

            foreach ($existingParcels as $parcel) {
                // Calcula el área del cuartel (asumiendo que 'surface' es el área original en Parcel)
                $area = ProgramParcel::where('program_id', $program->id)
                    ->where('parcel_id', $parcel->id)
                    ->value('area') ?? $parcel->surface;

                if ($area > 0) {
                    ProgramParcel::updateOrCreate(
                        [
                            'program_id' => $program->id,
                            'parcel_id' => $parcel->id,
                            'fertilizer_mapping_id' => $programFertilizer->fertilizer_mapping_id,
                        ],
                        [
                            'field_id' => $programFertilizer->field_id,
                            'area' => $area,
                            'fertilizer_amount' => $programFertilizer->units_per_ha * $area,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]
                    );
                }
            }
        });
        static::updated(function ($programFertilizer) {
            if ($programFertilizer->wasChanged('units_per_ha')) { // Solo si cambió units_per_ha
                ProgramParcel::where('program_id', $programFertilizer->program_id)
                    ->where('fertilizer_mapping_id', $programFertilizer->fertilizer_mapping_id)
                    ->each(function ($programParcel) use ($programFertilizer) {
                        $programParcel->update([
                            'fertilizer_amount' => $programFertilizer->units_per_ha * $programParcel->area,
                            'updated_by' => Auth::id(),
                        ]);
                    });
            }
        });

        // Evento: Al eliminar un ProgramFertilizer, elimina los ProgramParcel relacionados
        static::deleted(function ($programFertilizer) {
            ProgramParcel::where('program_id', $programFertilizer->program_id)
                ->where('fertilizer_mapping_id', $programFertilizer->fertilizer_mapping_id)
                ->delete();
        });
    
    });
    }
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function fertilizerMapping()
    {
        return $this->belongsTo(FertilizerMapping::class);
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
