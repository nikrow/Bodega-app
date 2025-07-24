<?php

namespace App\Exports;

use App\Models\OrderApplication;
use App\Models\OrderParcel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrderApplicationExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $query = OrderParcel::query()
            ->leftJoin('order_applications', function ($join) {
                $join->on('order_applications.order_id', '=', 'order_parcels.order_id')
                     ->on('order_applications.parcel_id', '=', 'order_parcels.parcel_id');
            })
            ->with(['order', 'parcel'])
            ->select([
                'order_parcels.id as order_parcel_id',
                'order_parcels.order_id',
                'order_parcels.parcel_id',
                'order_parcels.field_id',
                'order_applications.id as application_id',
                'order_applications.created_at as application_created_at',
                'order_applications.liter',
                'order_applications.surface',
                'order_applications.parcel_surface_snapshot',
                DB::raw('COALESCE(order_parcels.created_at, order_applications.created_at) as sort_date'), // Para ordenar
            ]);

        // Aplicar filtros al query principal
        if ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $q->whereDate('order_applications.created_at', '>=', $startDate)
                  ->orWhereNull('order_applications.created_at');
            });
        }

        if ($endDate) {
            $query->where(function ($q) use ($endDate) {
                $q->whereDate('order_applications.created_at', '<=', $endDate)
                  ->orWhereNull('order_applications.created_at');
            });
        }

        if (!empty($this->filters['field_id'])) {
            $query->where('order_parcels.field_id', $this->filters['field_id']);
        }

        if (!empty($this->filters['crop_id'])) {
            $query->whereHas('parcel', function ($q) {
                $q->where('crop_id', $this->filters['crop_id']);
            });
        }

        if (!empty($this->filters['orderNumber'])) {
            $query->whereHas('order', function ($q) {
                $q->where('orderNumber', $this->filters['orderNumber']);
            });
        }

        return $query->orderByDesc('sort_date');
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Cuartel',
            'Cuartel ID',
            'Order ID',
            'Litros aplicados',
            'Superficie aplicada',
            'Superficie del snapshot',
        ];
    }

    public function map($record): array
    {
        $hasApplication = !is_null($record->application_id);

        $fecha = $hasApplication && $record->application_created_at
            ? Carbon::parse($record->application_created_at)->format('d/m/Y')
            : 'N/A';

        $litrosAplicados = $hasApplication ? ($record->liter ?? 0) : 0;
        $superficieAplicada = $hasApplication ? ($record->surface ?? 0) : 0;

        // Ajuste: Si hay aplicación, usar snapshot de la app; si no, fallback a superficie actual de Parcel
        $superficieSnapshot = $hasApplication
            ? ($record->parcel_surface_snapshot ?? 0)
            : ($record->parcel->surface ?? 0);

        return [
            $fecha,
            $record->parcel->name ?? 'N/A',
            $record->parcel_id,
            $record->order_id,
            number_format($litrosAplicados, 2, ',', '.'),
            number_format($superficieAplicada, 2, ',', '.'),
            number_format($superficieSnapshot, 2, ',', '.'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Litros aplicados
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Superficie aplicada
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Superficie del snapshot
        ];
    }
}