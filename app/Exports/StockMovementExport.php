<?php

namespace App\Exports;

use App\Enums\MovementType;
use App\Models\StockMovement;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StockMovementExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = StockMovement::query()
            ->with([
                'producto',
                'warehouse',
                'user',
                'field' 
            ]);

        // Aplicar filtros
        if (!empty($this->filters['movement_type'])) {
            $query->where('movement_type', $this->filters['movement_type']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'ID',
            'Campo',
            'Tipo',
            'Producto',
            'Bodega',
            'Cantidad',
            'Orden',
            'Descripción',
            'Creado por',
        ];
    }

    public function map($record): array
    {
        $negativeTypes = [
            MovementType::SALIDA->value,
            MovementType::TRASLADO_SALIDA->value,
            MovementType::PREPARACION->value,
        ];

        return [
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->related_id,
            $record->field?->name ?? 'N/A',
            MovementType::tryFrom($record->movement_type)?->getLabel() ?? $record->movement_type,
            $record->producto?->product_name,
            $record->warehouse?->name,
            in_array($record->movement_type, $negativeTypes)
                ? -abs($record->quantity_change)
                : abs($record->quantity_change),
            $record->order_number ?? '-',
            $record->description,
            $record->user?->name,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Cantidad
        ];
    }
}