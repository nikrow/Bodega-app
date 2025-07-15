<?php

namespace App\Http\Controllers;

use App\Exports\OrderExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class OrderExportController extends Controller
{
    public function export(Request $request)
    {
        // Obtener filtros desde la solicitud
        $filters = $request->only([
            'field_id',
            'created_at_from',
            'created_at_to',
        ]);

        // Generar un nombre de archivo con la fecha actual
        $fileName = 'ordenes_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Descargar el archivo Excel
        return Excel::download(new OrderExport($filters), $fileName);
    }
}