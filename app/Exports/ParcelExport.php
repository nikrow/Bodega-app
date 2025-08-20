<?php

namespace App\Exports;

use App\Models\Parcel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class ParcelExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Parcel::query()
            ->with([
                'field',
                'crop',
                'parcelCropDetails.variety',
                'parcelCropDetails.rootstock',
                'parcelCropDetails.plantingScheme',
                'createdBy',
                'updatedBy',
                'deactivatedBy'
            ]);

        // Aplicar filtros
        if (!empty($this->filters['crop_id'])) {
            $query->whereHas('crop', function ($q) {
                $q->where('id', $this->filters['crop_id']);
            });
        }

        if (!empty($this->filters['is_active'])) {
            $query->where('is_active', $this->filters['is_active'] === 'true' ? 1 : 0);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Estanque',
            'Nombre',
            'Campo',
            'Cultivo',
            'Año de Plantación',
            'Plantas',
            'Superficie',
            'SDP',
            'Detalles de Variedades y Portainjertos',
            'Activa',
            'Desactivada En',
            'Razón de Desactivación',
            'Creado Por',
            'Modificado Por',
            'Desactivado Por',
        ];
    }

    public function map($parcel): array
    {
        // Separamos la lógica para los detalles de las variedades
        $subsectorDetails = $parcel->parcelCropDetails->map(function ($detail) {
            $parts = [];
            
            // Verificamos si los datos existen antes de agregarlos a los detalles
            if ($detail->subsector) {
                $parts[] = 'Subsector: ' . $detail->subsector;
            }
            if ($detail->variety) {
                $parts[] = 'Variedad: ' . $detail->variety->name;
            }
            if ($detail->rootstock) {
                $parts[] = 'Portainjerto: ' . $detail->rootstock->name;
            }
            if ($detail->plantingScheme) {
                $parts[] = 'Marco: ' . $detail->plantingScheme->scheme;
            }
            if ($detail->surface) {
                $parts[] = 'Superficie: ' . $detail->surface;
            }
            if ($detail->irrigation_system) { // Incluimos el sistema de riego
                $parts[] = 'Sistema de Riego: ' . $detail->irrigation_system;
            }
            
            return implode('; ', $parts);
        })->implode(' | ');

        return [
            $parcel->id,
            $parcel->tank,
            $parcel->name,
            $parcel->field?->name ?? 'null',
            $parcel->crop?->especie ?? 'null',
            $parcel->planting_year ?? 'null',
            $parcel->plants ?? 'null',
            $parcel->surface,
            $parcel->sdp ?? 'null',
            $subsectorDetails,
            $parcel->is_active ? 'Sí' : 'No',
            $parcel->deactivated_at
                ? Date::PHPToExcel(Carbon::parse($parcel->deactivated_at))
                : null,
            $parcel->deactivation_reason ?? 'null',
            $parcel->createdBy?->name ?? 'null',
            $parcel->updatedBy?->name ?? 'null',
            $parcel->deactivatedBy?->name ?? 'null',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}