<?php
namespace App\Exports;

use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Report::with(['tractor', 'machinery', 'operator', 'field', 'work'])
            ->get()
            ->map(function ($report) {
                return [
                    'ID' => $report->id,
                    'Fecha' => $report->date->format('Y-m-d'),
                    'Operador' => $report->operator->name ?? 'N/A',
                    'Campo' => $report->field->name ?? 'N/A',
                    'Proveedor' => $report->machinery_id 
                        ? ($report->machinery->provider->getLabel() ?? 'N/A') 
                        : ($report->tractor->provider->getLabel() ?? 'N/A'),
                    'Centro de costo' => $report->work->cost_type->getLabel()?? 'N/A',
                    'Tractor' => $report->tractor->name ?? 'N/A',
                    'Maquinaria' => $report->machinery->name ?? 'N/A',
                    'Trabajo' => $report->work->name ?? 'N/A',
                    'Horómetro Inicial' => $report->initial_hourometer,
                    'Horómetro Final' => $report->hourometer,
                    'Horas' => $report->hours,
                    'Total Tractor' => $report->tractor_total,
                    'Total Maquinaria' => $report->machinery_total,
                    'Aprobado' => $report->approved ? 'Sí' : 'No',
                    'Aprobado Por' => $report->approvedBy->name ?? 'N/A',
                    'Observaciones' => $report->observations ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Operador',
            'Campo',
            'Proveedor',
            'Centro de Costo',
            'Tractor',
            'Maquinaria',
            'Trabajo',
            'Horómetro Inicial',
            'Horómetro Final',
            'Horas',
            'Total Tractor',
            'Total Maquinaria',
            'Aprobado',
            'Aprobado Por',
            'Observaciones',
        ];
    }
}