<?php

namespace App\Exports;

use App\Models\OrderApplication;
use App\Models\OrderLine;
use App\Models\OrderParcel;
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
        // Subconsulta para obtener aplicaciones existentes con sus líneas de orden
        $applicationsQuery = OrderApplication::query()
            ->leftJoin('order_lines', 'order_applications.order_id', '=', 'order_lines.order_id')
            ->with([
                'order',
                'parcel.crop',
                'order.user',
                'order.field',
                'applicators',
                'order.orderLines.product', // Cargar la relación de productos desde OrderLine
            ])
            ->select([
                'order_applications.*',
                'order_lines.product_id as application_product_id',
                'order_lines.dosis as application_dosis',
                'order_lines.waiting_time as application_waiting_time',
                'order_lines.reentry as application_reentry',
            ]);

        // Aplicar filtros
        if (!empty($this->filters['crop_id'])) {
            $applicationsQuery->whereHas('parcel.crop', function ($q) {
                $q->where('id', $this->filters['crop_id']);
            });
        }

        if (!empty($this->filters['start_date'])) {
            $applicationsQuery->whereDate('order_applications.created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $applicationsQuery->whereDate('order_applications.created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['orderNumber'])) {
            $applicationsQuery->whereHas('order', function ($q) {
                $q->where('orderNumber', $this->filters['orderNumber']);
            });
        }

        // Subconsulta para obtener todos los cuarteles de OrderParcel
        $orderParcelsQuery = OrderParcel::query()
            ->with([
                'order',
                'parcel.crop',
                'order.user',
                'order.field',
                'order.orderLines.product', // Cargar la relación de productos desde OrderLine
            ])
            ->leftJoinSub($applicationsQuery, 'applications', function ($join) {
                $join->on('order_parcels.order_id', '=', 'applications.order_id')
                     ->on('order_parcels.parcel_id', '=', 'applications.parcel_id');
            })
            ->select([
                'order_parcels.*',
                'applications.id as application_id',
                'applications.created_at as application_created_at',
                'applications.liter as application_liter',
                'applications.surface as application_surface',
                'applications.wetting as application_wetting',
                'applications.wind_speed as application_wind_speed',
                'applications.temperature as application_temperature',
                'applications.moisture as application_moisture',
                'applications.application_product_id',
                'applications.application_dosis',
                'applications.application_waiting_time',
                'applications.application_reentry',
            ]);

        // Aplicar filtros adicionales a OrderParcel
        if (!empty($this->filters['field_id'])) {
            $orderParcelsQuery->where('order_parcels.field_id', $this->filters['field_id']);
        }

        return $orderParcelsQuery->orderBy('order_parcels.created_at', 'desc');
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
            'Porcentaje del Cuartel Aplicado',
        ];
    }

    public function map($record): array
    {
        // Determinar si hay una aplicación asociada
        $hasApplication = !is_null($record->application_id);
        $applicators = $hasApplication && $record->applicators
            ? $record->applicators->pluck('name')->join(', ')
            : 'N/A';

        // Calcular el porcentaje aplicado (como en OrderParcelRelationManager)
        $percentageApplied = 0;
        if ($record->order && $record->parcel) {
            $parcelSurface = $record->parcel->surface ?? 0;
            $totalSurfaceApplied = OrderApplication::where('order_id', $record->order_id)
                ->where('parcel_id', $record->parcel_id)
                ->sum('surface');
            if ($parcelSurface > 0) {
                $percentageApplied = ($totalSurfaceApplied / $parcelSurface) * 100;
                $percentageApplied = number_format($percentageApplied, 3, ',', '.');
            }
        }

        // Obtener el producto desde la relación order.orderLines, si existe
        $product = null;
        $orderLine = $record->order && $record->order->orderLines && $record->order->orderLines->isNotEmpty()
            ? $record->order->orderLines->first()
            : null;
        if ($orderLine && $orderLine->product) {
            $product = $orderLine->product;
        }

        // Obtener la dosis y otros datos desde orderLines
        $dosis = $hasApplication && $record->application_dosis
            ? number_format($record->application_dosis, 3, ',', '.')
            : 0;

        // Producto utilizado (no disponible en OrderLine, asumir 0 para consistencia)
        $productUsage = 0; // Nota: OrderLine no tiene product_usage, ajustar si es necesario

        return [
            // Fecha
            $hasApplication && $record->application_created_at
                ? Date::PHPToExcel(Carbon::parse($record->application_created_at))
                : null,
            // ID Aplicación
            $record->application_id ?? 'N/A',
            // ID Orden
            $record->order_id ?? 'N/A',
            // ID Campo
            $record->field_id ?? $record->order?->field_id ?? 'N/A',
            // ID Cuartel
            $record->parcel_id ?? 'N/A',
            // ID Producto
            $hasApplication && $record->application_product_id
                ? $record->application_product_id
                : 'N/A',
            // ID Encargado
            $record->order?->user?->id ?? 'N/A',
            // Campo
            $record->order?->field?->name ?? 'N/A',
            // Cuartel
            $record->parcel?->name ?? 'N/A',
            // Objetivo
            $record->order && is_array($record->order->objective)
                ? implode(', ', array_filter($record->order->objective))
                : ($record->order?->objective ?? 'N/A'),
            // Cultivo
            $record->parcel?->crop?->especie ?? 'N/A',
            // Número de orden
            $record->order?->orderNumber ?? 'N/A',
            // Producto
            $product ? $product->product_name : 'N/A',
            // Ingrediente activo
            $product ? $product->active_ingredients : 'N/A',
            // Carencia
            $hasApplication && $record->application_waiting_time
                ? $record->application_waiting_time
                : 'N/A',
            // Fecha de Reingreso
            $hasApplication && $record->application_created_at && $record->application_reentry
                ? Date::PHPToExcel(Carbon::parse($record->application_created_at)->addHours($record->application_reentry))
                : null,
            // Reanudar Cosecha
            $hasApplication && $record->application_created_at && $record->application_waiting_time
                ? Date::PHPToExcel(Carbon::parse($record->application_created_at)->addDays($record->application_waiting_time))
                : null,
            // Dosis L/100
            $dosis,
            // Mojamiento L/Ha
            $hasApplication && $record->application_wetting
                ? number_format($record->application_wetting, 0, ',', '.')
                : 0,
            // Litros aplicados
            $hasApplication && $record->application_liter
                ? number_format($record->application_liter, 2, ',', '.')
                : 0,
            // Superficie aplicada
            $hasApplication && $record->application_surface
                ? number_format($record->application_surface, 2, ',', '.')
                : 0,
            // Producto utilizado
            $productUsage,
            // Equipamiento usado
            $record->order && is_array($record->order->equipment)
                ? implode(', ', $record->order->equipment)
                : ($record->order?->equipment ?? 'N/A'),
            // Aplicadores
            $applicators,
            // Encargado
            $record->order?->user?->name ?? 'N/A',
            // Temperatura °C
            $hasApplication && $record->application_temperature
                ? number_format($record->application_temperature, 1, ',', '.')
                : 0,
            // Velocidad del viento km/hr
            $hasApplication && $record->application_wind_speed
                ? number_format($record->application_wind_speed, 0, ',', '.')
                : 0,
            // Humedad %
            $hasApplication && $record->application_moisture
                ? number_format($record->application_moisture, 1, ',', '.')
                : 0,
            // Costo aplicación USD
            0, // Nota: OrderLine no tiene total_cost, ajustar si es necesario
            // Porcentaje del Cuartel Aplicado
            $percentageApplied,
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
            'AC' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Porcentaje del Cuartel Aplicado
        ];
    }
}