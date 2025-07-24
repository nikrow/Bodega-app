<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedReportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ConsolidatedReportExportController extends Controller
{
    /**
     * Exportar el reporte consolidado como Excel.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $filename = Carbon::today()->format('Y-m-d') . ' - Export Reportes Consolidados.xlsx';

        return Excel::download(new ConsolidatedReportExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}