<?php

namespace App\Models;

use Filament\Facades\Filament;
use App\Models\Program;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramParcel extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'parcel_id',
        'field_id',
        'area',
        'fertilizer_mapping_id',
        'fertilizer_amount',
        'created_by',
        'updated_by',
    ];
    protected $table = 'program_parcels';
    protected $casts = [
        'area' => 'decimal:2',
        'fertilizer_amount' => 'decimal:2',
    ];

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
        if ($model->area <= 0 || $model->fertilizer_amount <= 0) {
            throw new \Exception('El área y la cantidad de fertilizante deben ser mayores que cero.');
        }
        $program = Program::find($model->program_id);
            if (!$program) return; // Si el programa no existe, no continuar

            $newStartDate = $program->start_date;
            $newEndDate = $program->end_date;
            $parcelId = $model->parcel_id;

            // Buscamos si el cuartel ya está en otro programa con fechas solapadas.
            // La lógica es: (start1 <= end2) y (start2 <= end1)
            $conflictingProgram = Program::where('id', '!=', $program->id) // Excluir el programa actual
                ->whereHas('parcels', function ($query) use ($parcelId) {
                    $query->where('parcel_id', $parcelId);
                })
                ->where('start_date', '<=', $newEndDate)
                ->where('end_date', '>=', $newStartDate)
                ->first(); // Obtenemos el primer conflicto encontrado

            if ($conflictingProgram) {
                // Si se encuentra un programa en conflicto, lanzamos una excepción.
                throw new \Exception(
                    "El cuartel ya está asignado al programa '{$conflictingProgram->name}' (" .
                    $conflictingProgram->start_date->format('d-m-Y') . " al " .
                    $conflictingProgram->end_date->format('d-m-Y') . "), " .
                    "cuyo período se solapa con el actual."
                );
            }
    });
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }


    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    public function fertilizerMapping()
    {
        return $this->belongsTo(FertilizerMapping::class, 'fertilizer_mapping_id');
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
