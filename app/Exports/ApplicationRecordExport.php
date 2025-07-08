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
                'field'
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
            'Campo', // Nueva columna
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
            $record->order_application_id,
            $record->field?->name ?? 'N/A',
            $record->parcel?->name,
            is_array($record->order?->objective)
                ? implode(', ', array_filter($record->order->objective))
                : $record->order?->objective,
            $record->parcel?->crop?->especie,
            $record->orderNumber,
            $record->product?->product_name,
            $record->product?->active_ingredients,
            $record->product?->waiting_time,
            $record->created_at && $record->product?->reentry
                ? Date::PHPToExcel($record->created_at->copy()->addHours($record->product->reentry))
                : null,
            $record->created_at && $record->product?->waiting_time
                ? Date::PHPToExcel($record->created_at->copy()->addDays($record->product->waiting_time))
                : null,
            $record->dose_per_100l,
            $record->order?->wetting,
            $record->liters_applied,
            $record->orderApplication?->surface,
            $record->product_usage,
            is_array($record->order?->equipment)
                ? implode(', ', $record->order->equipment)
                : $record->order?->equipment,
            $record->applicators_details,
            $record->order?->user?->name,
            $record->orderApplication?->temperature,
            $record->orderApplication?->wind_speed,
            $record->orderApplication?->moisture,
            $record->total_cost,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'L' => NumberFormat::FORMAT_DATE_DDMMYYYY, 
        ];
    }
}