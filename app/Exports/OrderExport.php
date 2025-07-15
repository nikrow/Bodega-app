<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrderExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Order::query()
            ->with([
                'field',
                'crop',
                'warehouse',
                'user',
                'createdBy',
                'updatedBy',
                'parcels',
            ]);

        // Aplicar filtros si se proporcionan (por ejemplo, por fecha o campo)
        if (!empty($this->filters['field_id'])) {
            $query->where('field_id', $this->filters['field_id']);
        }

        if (!empty($this->filters['created_at_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_at_from']);
        }

        if (!empty($this->filters['created_at_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_at_to']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Orden',
            'ID Campo',
            'ID Cultivo',
            'ID Almacén',
            'ID Usuario',
            'ID Creado por',
            'ID Actualizado por',
            'Número de orden',
            'Fecha Creación',
            'Fecha Actualización',
            'Campo',
            'Cultivo',
            'Almacén',
            'Usuario',
            'Creado por',
            'Actualizado por',
            'Cuarteles',
            'Área Total (ha)',
            'Porcentaje Aplicado Total (%)',
            'Mojamiento',
            'Equipos',
            'Familia',
            'EPP',
            'Aplicadores',
            'Objetivo',
            'Observaciones',
            'Indicaciones',
            'Completado',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->field_id ?? 'N/A',
            $record->crops_id ?? 'N/A',
            $record->warehouse_id ?? 'N/A',
            $record->user_id ?? 'N/A',
            $record->created_by ?? 'N/A',
            $record->updated_by ?? 'N/A',
            $record->orderNumber,
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->updated_at ? Date::PHPToExcel(Carbon::parse($record->updated_at)) : null,
            $record->field?->name ?? 'N/A',
            $record->crop?->name ?? 'N/A',
            $record->warehouse?->name ?? 'N/A',
            $record->user?->name ?? 'N/A',
            $record->createdBy?->name ?? 'N/A',
            $record->updatedBy?->name ?? 'N/A',
            $record->parcels->pluck('name')->join(', ') ?: 'N/A',
            number_format($record->total_area, 2, ',', '.'),
            number_format($record->total_applied_percentage, 2, ',', '.'),
            $record->wetting ? number_format($record->wetting, 0, ',', '.') : 0,
            is_array($record->equipment) ? implode(', ', $record->equipment) : ($record->equipment ?? 'N/A'),
            is_array($record->family) ? implode(', ', $record->family) : ($record->family ?? 'N/A'),
            is_array($record->epp) ? implode(', ', $record->epp) : ($record->epp ?? 'N/A'),
            is_array($record->applicators) ? implode(', ', $record->applicators) : ($record->applicators ?? 'N/A'),
            $record->objective ?? 'N/A',
            $record->observations ?? 'N/A',
            $record->indications ?? 'N/A',
            $record->is_completed ? 'Sí' : 'No',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Creación
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Actualización
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Área Total
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Porcentaje Aplicado Total
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Mojamiento
        ];
    }
}