<?php

namespace App\Http\Controllers;

use App\Exports\ProgramExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ProgramExportController extends Controller
{
    /**
     * Maneja la solicitud para descargar el reporte de Programas en formato Excel.
     */
    public function exportExcel()
    {
        // Genera un nombre de archivo dinámico con la fecha actual.
        // Ejemplo: "2025-08-12 - Programas.xlsx"
        $filename = Carbon::today()->format('Y-m-d') . ' - Programas.xlsx';

        // Llama al Facade de Excel para descargar el archivo.
        // Instancia la clase ProgramExport que creamos en el paso anterior.
        return Excel::download(new ProgramExport(), $filename);
    }
}