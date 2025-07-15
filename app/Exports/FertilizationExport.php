<?php

namespace App\Exports;

use App\Models\Fertilization;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FertilizationExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Fertilization::query()
            ->with([
                'parcel',
                'fertilizerMapping.product',
                'field',
            ]);

        // Aplicar filtros
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('date', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('date', '<=', $this->filters['end_date']);
        }

        return $query->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Campo',
            'Cuartel',
            'Fertilizante',
            'Superficie',
            'Cantidad Solución',
            'Factor de Dilución',
            'Producto',
            'Código SAP',
            'Cantidad Producto',
            'Precio Producto',
            'Costo Total',
            'Método Aplicación',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->date ? Date::PHPToExcel(Carbon::parse($record->date)) : null,
            $record->field?->name ?? 'N/A',
            $record->parcel?->name,
            $record->fertilizerMapping
                ? ($record->fertilizerMapping->fertilizer_name . ' (' . $record->fertilizerMapping->product->product_name . ')')
                : 'N/A',
            $record->surface,
            $record->quantity_solution,
            $record->dilution_factor,
            $record->product?->product_name,
            $record->product?->SAP_code,
            $record->quantity_product,
            $record->product_price ? number_format($record->product_price, 2, '.', '') : 0,
            $record->total_cost ? number_format($record->total_cost, 2, '.', '') : 0,
            $record->application_method,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Superficie
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Cantidad Solución
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Factor de Dilución
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Cantidad Producto
            'L' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER, // Precio Producto
            'M' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER, // Costo Total
        ];
    }
}