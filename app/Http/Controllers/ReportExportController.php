<?php

namespace App\Http\Controllers;

use App\Exports\ReportsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ReportExportController extends Controller
{
    public function exportExcel()
    {
        return Excel::download(new ReportsExport, 'reports.xlsx');
    }
}