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
                'plantingScheme',
                'parcelCropDetails.variety',
                'parcelCropDetails.rootstock',
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
            'Nombre',
            'Campo',
            'Cultivo',
            'Año de Plantación',
            'Plantas',
            'Superficie',
            'SDP',
            'Sistema de Riego',
            'Marco de Plantación',
            'Variedades y Portainjertos',
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
        return [
            $parcel->id,
            $parcel->name,
            $parcel->field?->name ?? 'N/A',
            $parcel->crop?->especie ?? 'N/A',
            $parcel->planting_year ?? 'N/A',
            $parcel->plants ?? 'N/A',
            $parcel->surface,
            $parcel->sdp ?? 'N/A',
            $parcel->irrigation_system ?? 'N/A',
            $parcel->plantingScheme?->scheme ?? 'N/A',
            $parcel->parcelCropDetails->isNotEmpty()
                ? $parcel->parcelCropDetails->map(function ($detail) {
                    return [
                        'variedad' => $detail->variety?->name ?? 'N/A',
                        'portainjerto' => $detail->rootstock?->name ?? 'No tiene',
                        'superficie' => $detail->surface,
                    ];
                })->toJson()
                : 'N/A',
            $parcel->is_active ? 'Sí' : 'No',
            $parcel->deactivated_at
                ? Date::PHPToExcel(Carbon::parse($parcel->deactivated_at))
                : null,
            $parcel->deactivation_reason ?? 'N/A',
            $parcel->createdBy?->name ?? 'N/A',
            $parcel->updatedBy?->name ?? 'N/A',
            $parcel->deactivatedBy?->name ?? 'N/A',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'M' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Columna 'Desactivada En'
        ];
    }
}