<?php

namespace App\Http\Controllers;

use App\Exports\ParcelExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ParcelExportController extends Controller
{
    public function exportExcel()
    {
        try {
        return Excel::download(new ParcelExport, 'cuarteles.xlsx');
    } catch (\Exception $e) {
        Log::error('Error al exportar Excel: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error al generar el archivo.');
    }
    }
}