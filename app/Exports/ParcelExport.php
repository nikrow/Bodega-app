<?php

namespace App\Exports;

use App\Models\Parcel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParcelExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Parcel::with(['field', 'crop'])
            ->get()
            ->map(function ($parcel) {
                return [
                    'ID' => $parcel->id,
                    'Nombre' => $parcel->name,
                    'Campo' => $parcel->field->name ?? 'N/A',
                    'Cultivo' => $parcel->crop->especie ?? 'N/A',
                    'Año de plantación' => $parcel->planting_year ?? 'N/A',
                    'Plantas' => $parcel->plants ?? 'N/A',
                    'Superficie' => $parcel->surface,
                    'Activa' => $parcel->is_active ? 'Sí' : 'No',
                    'Desactivada En' => $parcel->deactivated_at ? $parcel->deactivated_at->format('d/m/Y') : 'N/A',
                    'Razón de Desactivación' => $parcel->deactivation_reason ?? 'N/A',
                ];
            });
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
            'Activa',
            'Desactivada En',
            'Razón de Desactivación',
        ];
    }
}