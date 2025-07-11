<?php

namespace App\Exports;

use App\Models\OrderApplication;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrderApplicationExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = OrderApplication::query()
            ->with([
                'parcel',
                'createdBy',
                'order',
                'order.field',
                'applicators',
            ]);

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Aplicación',
            'ID Orden',
            'ID Campo',
            'ID Cuartel',
            'ID Creado por',
            'Fecha Aplicación',
            'Campo',
            'Número de orden',
            'Cuartel',
            'Litros Aplicados',
            'Superficie Aplicada',
            'Porcentaje del Cuartel Aplicado',
            'Creado por',
            'Mojamiento',
            'Viento',
            'Temperatura',
            'Humedad',
            'Aplicadores',
        ];
    }

    public function map($record): array
    {
        $applicators = $record->applicators->pluck('name')->join(', ') ?: 'N/A';

        return [
            $record->id,
            $record->order_id ?? 'N/A',
            $record->field_id ?? $record->order?->field_id ?? 'N/A',
            $record->parcel_id ?? 'N/A',
            $record->created_by ?? 'N/A',
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->order?->field?->name ?? 'N/A',
            $record->order?->orderNumber ?? ($record->order_id ? 'Orden no encontrada' : 'Sin orden'),
            $record->parcel?->name ?? 'N/A',
            $record->liter ? number_format($record->liter, 0, ',', '.') : 0,
            $record->surface ? number_format($record->surface, 2, ',', '.') : 0,
            $record->application_percentage ? number_format($record->application_percentage, 2, ',', '.') : 0,
            $record->createdBy?->name ?? 'N/A',
            $record->wetting ? number_format($record->wetting, 0, ',', '.') : 0,
            $record->wind_speed ? number_format($record->wind_speed, 0, ',', '.') : 0,
            $record->temperature ? number_format($record->temperature, 1, ',', '.') : 0,
            $record->moisture ? number_format($record->moisture, 1, ',', '.') : 0,
            $applicators,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Aplicación
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Litros Aplicados
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Superficie Aplicada
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Porcentaje del Cuartel Aplicado
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Mojamiento
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Viento
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Temperatura
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Humedad
        ];
    }
}