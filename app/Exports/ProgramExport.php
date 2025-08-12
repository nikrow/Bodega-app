<?php

namespace App\Exports;

use App\Models\ProgramParcel;
use App\Models\ProgramFertilizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProgramExport implements FromQuery, WithHeadings, WithMapping
{
    protected Collection $programFertilizers;
    protected ?int $tenantId;
    protected ?int $programId;
    /**
     * El constructor ahora acepta un ID de tenant opcional.
     * @param int|null $tenantId
     * @param int|null $programId
     */
    public function __construct(?int $tenantId = null, ?int $programId = null)
    {
        $this->tenantId = $tenantId;
        $this->programId = $programId;

        $query = ProgramFertilizer::query();

        if ($this->tenantId) {
            $query->where('field_id', $this->tenantId);
        }
        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }

        $this->programFertilizers = $query->get()
            ->keyBy(fn($pf) => $pf->program_id . '-' . $pf->fertilizer_mapping_id);
    }

    /**
     * La consulta base para la exportación.
     */
    public function query()
    {
        $query = ProgramParcel::query()
            ->with([
                'program.field',
                'program.crop',
                'fertilizerMapping.product',
            ])
            ->whereNotNull('fertilizer_mapping_id');

        // Aplicamos el filtro de tenant a la consulta principal, solo si existe un ID.
        if ($this->tenantId) {
            $query->where('field_id', $this->tenantId);
        }
        // Aplicamos el filtro de programa si existe.
        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Campo', 'Field_id', 'Programa', 'Program_id', 'Cultivo', 'Cuartel',
            'Parcel_id', 'Superficie (ha)', 'Cantidad de Aplicaciones',
            'Fertilizante', 'Fertilizer_mapping_id', 'Producto', 'Product_id',
            'Cantidad Fertilizante Bruto', 'Factor de Dilución', 'Litros Diluidos (Calculado)',
        ];
    }

    public function map($programParcel): array
    {
        $key = $programParcel->program_id . '-' . $programParcel->fertilizer_mapping_id;
        $programFertilizer = $this->programFertilizers->get($key);
        $dilutionFactor = $programFertilizer?->dilution_factor ?? 0;
        $fertilizerAmount = $programParcel->fertilizer_amount ?? 0;

        return [
            $programParcel->program?->field?->name ?? 'N/A',
            $programParcel->program?->field_id ?? 'N/A',
            $programParcel->program?->name ?? 'N/A',
            $programParcel->program_id,
            $programParcel->program?->crop?->especie ?? 'N/A',
            $programParcel->parcel?->name ?? 'N/A',
            $programParcel->parcel_id,
            $programParcel->area,
            $programFertilizer?->application_quantity ?? 'N/A',
            $programParcel->fertilizerMapping?->fertilizer_name ?? 'N/A',
            $programParcel->fertilizer_mapping_id,
            $programParcel->fertilizerMapping?->product?->product_name ?? 'N/A',
            $programParcel->fertilizerMapping?->product_id ?? 'N/A',
            $fertilizerAmount,
            $dilutionFactor,
            $fertilizerAmount * $dilutionFactor,
        ];
    }
}