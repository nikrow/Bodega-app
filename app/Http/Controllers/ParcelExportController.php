<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\ParcelExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ParcelExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['crop_id', 'is_active', 'start_date', 'end_date']);
        $filename = Carbon::today()->format('Y-m-d') . ' - Cuarteles.xlsx';

        return Excel::download(new ParcelExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}