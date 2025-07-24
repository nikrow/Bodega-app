<?php

namespace App\Exports;

use App\Models\ConsolidatedReport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ConsolidatedReportExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = ConsolidatedReport::query()
            ->join('reports', 'consolidated_reports.report_id', '=', 'reports.id')
            ->select('consolidated_reports.*')
            ->with([
                'report.work',
                'report.field',
                'report.operator',
                'report.approvedBy',
                'machinery',
                'tractor',
            ]);

        // Aplicar filtros de fecha
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('reports.date', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('reports.date', '<=', $this->filters['end_date']);
        }

        return $query->orderBy('reports.id', 'desc');
    }

    public function headings(): array
    {
        return [
            'Proveedor',
            'ID Report',
            'Fecha',
            'Campo',
            'Operador',
            'Equipo',
            'Horómetro inicial',
            'Horómetro final',
            'Horas',
            'Labores',
            'Centro de Costo',
            'Precio',
            'Total',
            'Aprobado por',
            'Generado',
        ];
    }

    public function map($record): array
    {
        $isMachinery = !is_null($record->machinery_id);

        return [
            // Proveedor
            $isMachinery ? ($record->machinery->provider ?? 'N/A') : ($record->tractor->provider ?? 'N/A'),
            // ID Report
            $record->report ? $record->report->id : 'Sin reporte',
            // Fecha
            $record->report && $record->report->date ? Carbon::parse($record->report->date)->format('d/m/Y') : 'N/A',
            // Campo
            $record->report && $record->report->field ? $record->report->field->name : 'N/A',
            // Operador
            $record->report && $record->report->operator ? $record->report->operator->name : 'N/A',
            // Equipo
            $isMachinery ? ($record->machinery->name ?? 'N/A') : ($record->tractor->name ?? 'N/A'),
            // Horómetro inicial
            $record->report ? number_format((float) $record->report->initial_hourometer, 1, ',', '.') : '0,0',
            // Horómetro final
            $record->report ? number_format((float) $record->report->hourometer, 1, ',', '.') : '0,0',
            // Horas
            number_format($isMachinery ? ($record->machinery_hours ?? 0) : ($record->tractor_hours ?? 0), 2, ',', '.'),
            // Labores
            $record->report && $record->report->work ? $record->report->work->name : 'N/A',
            // Centro de Costo
            $record->report && $record->report->work ? $record->report->work->cost_type : 'N/A',
            // Precio
            $isMachinery ? number_format($record->machinery->price ?? 0, 2, ',', '.') : number_format($record->tractor->price ?? 0, 2, ',', '.'),
            // Total
            number_format($isMachinery ? ($record->machinery_total ?? 0) : ($record->tractor_total ?? 0), 2, ',', '.'),
            // Aprobado por
            $record->report && $record->report->approvedBy ? $record->report->approvedBy->name : 'N/A',
            // Generado
            $record->generated_at ? Carbon::parse($record->generated_at)->format('d/m/Y H:i') : 'N/A',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Fecha
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Horómetro inicial
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Horómetro final
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Horas
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Precio
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total
            'O' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Generado (como fecha, sin hora por simplicidad)
        ];
    }
}