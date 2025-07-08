<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\ApplicationRecordExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationRecordExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['crop_id', 'start_date', 'end_date', 'orderNumber']);
        $filename = Carbon::today()->format('Y-m-d') . ' - Export Registro de aplicaciones.xlsx';

        return Excel::download(new ApplicationRecordExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}