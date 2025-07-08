<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\FertilizationExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FertilizationExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $filename = Carbon::today()->format('Y-m-d') . ' - Export Fertilizaciones.xlsx';

        return Excel::download(new FertilizationExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}