<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\OrderApplicationExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderApplicationExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']); // Preparado para filtros futuros
        $filename = Carbon::today()->format('Y-m-d') . ' - Export Aplicaciones.xlsx';

        return Excel::download(new OrderApplicationExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}