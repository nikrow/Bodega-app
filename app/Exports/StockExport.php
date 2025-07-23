<?php

namespace App\Exports;

use App\Models\Stock;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StockExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Stock::query()
            ->with([
                'product',
                'field',
                'warehouse',
                'createdBy',
                'updatedBy',
            ]);

        // Aplicar filtros
        if (!empty($this->filters['product_id'])) {
            $query->where('product_id', $this->filters['product_id']);
        }

        if (!empty($this->filters['field_id'])) {
            $query->where('field_id', $this->filters['field_id']);
        }

        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Stock',
            'ID Producto',
            'ID Campo',
            'ID Almacén',
            'ID Creado por',
            'ID Actualizado por',
            'Fecha Creación',
            'Fecha Actualización',
            'Producto',
            'Campo',
            'Almacén',
            'Creado por',
            'Actualizado por',
            'Cantidad',
            'Precio Unitario',
            'Precio Total',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->product_id ?? 'N/A',
            $record->field_id ?? 'N/A',
            $record->warehouse_id ?? 'N/A',
            $record->created_by ?? 'N/A',
            $record->updated_by ?? 'N/A',
            $record->created_at ? Date::PHPToExcel(Carbon::parse($record->created_at)) : null,
            $record->updated_at ? Date::PHPToExcel(Carbon::parse($record->updated_at)) : null,
            $record->product?->product_name ?? 'N/A',
            $record->field?->name ?? 'N/A',
            $record->warehouse?->name ?? 'N/A',
            $record->createdBy?->name ?? 'N/A',
            $record->updatedBy?->name ?? 'N/A',
            $record->quantity ? number_format($record->quantity, 2, ',', '.') : 0,
            $record->price ? number_format($record->price, 2, ',', '.') : 0,
            $record->total_price ? number_format($record->total_price, 2, ',', '.') : 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Creación
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha Actualización
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Cantidad
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Precio Unitario
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Precio Total
        ];
    }
}