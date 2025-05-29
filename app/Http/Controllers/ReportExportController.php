<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ReportsExport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    public function exportExcel()
    {
        try {
        return Excel::download(new ReportsExport, 'reportes.xlsx');
    } catch (\Exception $e) {
        Log::error('Error al exportar Excel: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error al generar el archivo.');
    }
    }
}