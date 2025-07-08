<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\StockMovementExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockMovementExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['movement_type', 'start_date', 'end_date', 'warehouse_id']);
        $filename = Carbon::today()->format('Y-m-d') . ' - Export Movimientos Stock.xlsx';

        return Excel::download(new StockMovementExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}