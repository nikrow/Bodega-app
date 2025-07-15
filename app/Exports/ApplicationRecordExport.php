<?php

namespace App\Exports;

use App\Models\OrderApplicationUsage;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ApplicationRecordExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = OrderApplicationUsage::query()
            ->with([
                'order',
                'parcel.crop',
                'product',
                'orderApplication',
                'order.user',
                'field',
            ]);

        // Aplicar filtros
        if (!empty($this->filters['crop_id'])) {
            $query->whereHas('parcel.crop', function ($q) {
                $q->where('id', $this->filters['crop_id']);
            });
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['orderNumber'])) {
            $query->where('orderNumber', $this->filters['orderNumber']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'ID Aplicación',
            'ID Orden',
            'ID Campo',
            'ID Cuartel',
            'ID Producto',
            'ID Encargado',
            'Campo',
            'Cuartel',
            'Objetivo',
            'Cultivo',
            'Número de orden',
            'Producto',
            'Ingrediente activo',
            'Carencia',
            'Fecha de Reingreso',
            'Reanudar Cosecha',
            'Dosis L/100',
            'Mojamiento L/Ha',
            'Litros aplicados',
            'Superficie aplicada',
            'Producto utilizado',
            'Equipamiento usado',
            'Aplicadores',
            'Encargado',
            'Temperatura °C',
            'Velocidad del viento km/hr',
            'Humedad %',
            'Costo aplicación USD',
        ];
    }

    public function map($record): array
    {
        return [
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->order_application_id ?? 'N/A',
            $record->order?->id ?? 'N/A',
            $record->field?->id ?? $record->order?->field_id ?? 'N/A',
            $record->parcel?->id ?? 'N/A',
            $record->product?->id ?? 'N/A',
            $record->order?->user?->id ?? 'N/A',
            $record->field?->name ?? 'N/A',
            $record->parcel?->name ?? 'N/A',
            is_array($record->order?->objective)
                ? implode(', ', array_filter($record->order->objective))
                : ($record->order?->objective ?? 'N/A'),
            $record->parcel?->crop?->especie ?? 'N/A',
            $record->orderNumber ?? 'N/A',
            $record->product?->product_name ?? 'N/A',
            $record->product?->active_ingredients ?? 'N/A',
            $record->product?->waiting_time ?? 'N/A',
            $record->created_at && $record->product?->reentry
                ? Date::PHPToExcel($record->created_at->copy()->addHours($record->product->reentry))
                : null,
            $record->created_at && $record->product?->waiting_time
                ? Date::PHPToExcel($record->created_at->copy()->addDays($record->product->waiting_time))
                : null,
            $record->dose_per_100l ? number_format($record->dose_per_100l, 2, ',', '.') : 0,
            $record->order?->wetting ? number_format($record->order->wetting, 0, ',', '.') : 0,
            $record->liters_applied ? number_format($record->liters_applied, 2, ',', '.') : 0,
            $record->orderApplication?->surface ? number_format($record->orderApplication->surface, 2, ',', '.') : 0,
            $record->product_usage ? number_format($record->product_usage, 2, ',', '.') : 0,
            is_array($record->order?->equipment)
                ? implode(', ', $record->order->equipment)
                : ($record->order?->equipment ?? 'N/A'),
            $record->applicators_details ?? 'N/A',
            $record->order?->user?->name ?? 'N/A',
            $record->orderApplication?->temperature ? number_format($record->orderApplication->temperature, 1, ',', '.') : 0,
            $record->orderApplication?->wind_speed ? number_format($record->orderApplication->wind_speed, 0, ',', '.') : 0,
            $record->orderApplication?->moisture ? number_format($record->orderApplication->moisture, 1, ',', '.') : 0,
            $record->total_cost ? number_format($record->total_cost, 2, ',', '.') : 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha
            'P' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha de Reingreso
            'Q' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Reanudar Cosecha
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Dosis L/100
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Mojamiento L/Ha
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Litros aplicados
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Superficie aplicada
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Producto utilizado
            'Y' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Temperatura °C
            'Z' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Velocidad del viento km/hr
            'AA' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Humedad %
            'AB' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Costo aplicación USD
        ];
    }
}