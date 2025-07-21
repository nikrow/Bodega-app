<?php

namespace App\Exports;

use App\Models\OrderParcel;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrderParcelExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = OrderParcel::query()
            ->with([
                'order',
                'parcel',
                'field',
                'createdBy',
                'updatedBy',
            ]);

        // Aplicar filtros si se proporcionan
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
            'ID',
            'ID Orden',
            'ID Cuartel',
            'ID Campo',
            'ID Creado por',
            'ID Actualizado por',
            'Fecha Creación',
            'Fecha Actualización',
            'Orden Número',
            'Cuartel Nombre',
            'Campo Nombre',
            'Creado por',
            'Actualizado por',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->order_id ?? 'N/A',
            $record->parcel_id ?? 'N/A',
            $record->field_id ?? 'N/A',
            $record->created_by ?? 'N/A',
            $record->updated_by ?? 'N/A',
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->updated_at ? Date::PHPToExcel(Carbon::parse($record->updated_at)) : null,
            $record->order?->orderNumber ?? 'N/A',
            $record->parcel?->name ?? 'N/A',
            $record->field?->name ?? 'N/A',
            $record->createdBy?->name ?? 'N/A',
            $record->updatedBy?->name ?? 'N/A',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Creación
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Actualización
        ];
    }
}