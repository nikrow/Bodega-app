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
                'order.field', // Cargar la relación field a través de order
            ]);

        // Aplicar filtros (si se agregan en el futuro)
        // Por ahora, no hay filtros definidos en el recurso
        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Aplicación',
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
        return [
            $record->id,
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->order?->field?->name ?? 'N/A',
            $record->order?->ordernumber ?? 'N/A',
            $record->parcel?->name,
            $record->liter ? number_format($record->liter, 0, ',', '.') : 0,
            $record->surface ? number_format($record->surface, 2, ',', '.') : 0,
            $record->application_percentage ? number_format($record->application_percentage, 2, ',', '.') : 0,
            $record->createdBy?->name,
            $record->wetting ? number_format($record->wetting, 0, ',', '.') : 0,
            $record->wind_speed ? number_format($record->wind_speed, 0, ',', '.') : 0,
            $record->temperature ? number_format($record->temperature, 1, ',', '.') : 0,
            $record->moisture ? number_format($record->moisture, 1, ',', '.') : 0,
            $record->applicators_details ?? 'N/A',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Aplicación
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Litros Aplicados
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Superficie Aplicada
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Porcentaje del Cuartel Aplicado
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Mojamiento
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Viento
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Temperatura
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Humedad
        ];
    }
}