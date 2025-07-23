<?php

namespace App\Http\Controllers;

use App\Exports\StockExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class StockExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        // Obtener filtros desde la solicitud
        $filters = $request->only([
            'product_id',
            'field_id',
            'warehouse_id',
            'start_date',
            'end_date',
        ]);

        // Generar un nombre de archivo con la fecha actual
        $fileName = 'stock_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Descargar el archivo Excel
        return Excel::download(new StockExport($filters), $fileName);
    }
}