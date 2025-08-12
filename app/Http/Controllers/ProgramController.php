<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class ProgramController extends Controller
{
    public function downloadPdf(Program $program)
    {
        // 1. Cargar todas las relaciones necesarias para el PDF con un solo query.
        $program->load([
            'field', 
            'crop',  
            'fertilizers.fertilizerMapping', 
            'parcels',
        ]);
        // 2. Renderizar la vista Blade a HTML, pasando el objeto $program
        $html = view('pdf.program', compact('program'))->render();

        // 3. Usar Browsershot para convertir el HTML en un PDF
        $pdf = Browsershot::html($html)

            ->setNodeBinary('/home/nikrow/.nvm/versions/node/v22.18.0/bin/node')
            ->setNpmBinary('/home/nikrow/.nvm/versions/node/v22.18.0/bin/npm')
            ->format('A4') 
            ->landscape() 
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->noSandbox() 
            ->waitUntilNetworkIdle()
            ->pdf();

        // 4. Devolver el PDF para que se muestre en el navegador
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="programa_' . $program->name . '.pdf"');
    }
}