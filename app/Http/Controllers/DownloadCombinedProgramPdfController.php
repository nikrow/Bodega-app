<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class DownloadCombinedProgramPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1. Validar los datos de entrada (ahora esperamos un array de programas)
        $validated = $request->validate([
            'programs'   => 'required|array',
            'programs.*' => 'required|exists:programs,id', // Valida que cada ID exista
        ]);

        // 2. Obtener los programas seleccionados (la consulta es más simple ahora)
        $programs = Program::with([
                'parcels',
                'fertilizers.fertilizerMapping',
                'crop',
                'field' // Para obtener el nombre del campo/tenant
            ])
            ->whereIn('id', $validated['programs'])
            ->get();
        
        if ($programs->isEmpty()) {
            Filament::notify('warning', 'No se seleccionaron programas válidos.');
            return redirect()->back();
        }
        $minDate = $programs->min('start_date');
        $maxDate = $programs->max('end_date');
        $uniqueParcels = collect();
        $uniqueFertilizers = collect();
        $dataMatrix = [];

        foreach ($programs as $program) {
            foreach ($program->fertilizers as $fertilizer) {
                $uniqueFertilizers->put($fertilizer->id, $fertilizer);
                foreach ($program->parcels as $parcel) {
                    $uniqueParcels->put($parcel->id, $parcel);
                    $area = (float) $parcel->pivot->area;
                    $upa  = (float) $fertilizer->units_per_ha;
                    $df   = (float) ($fertilizer->dilution_factor ?: 1);
                    $fertilizerAmount = ($upa * $area) / $df;

                    if (!isset($dataMatrix[$parcel->id][$fertilizer->id])) {
                        $dataMatrix[$parcel->id][$fertilizer->id] = [
                            'amount' => 0,
                            'application_quantity' => $fertilizer->application_quantity,
                        ];
                    }
                    $dataMatrix[$parcel->id][$fertilizer->id]['amount'] += $fertilizerAmount;
                }
            }
        }
        
        $sortedParcels = $uniqueParcels->sortBy('name');
        $sortedFertilizers = $uniqueFertilizers->sortBy('fertilizerMapping.fertilizer_name');

        $firstProgram = $programs->first();

        // 2. Pasamos las nuevas variables a la vista en lugar de '$filters'
        $viewData = [
            'parcels' => $sortedParcels,
            'fertilizers' => $sortedFertilizers,
            'dataMatrix' => $dataMatrix,
            'cropName' => $firstProgram->crop->especie,
            'tenant' => $firstProgram->field,
            'minDate' => $minDate, 
            'maxDate' => $maxDate, 
        ];
        
        $html = view('pdf.combined-program', $viewData)->render();
        
        $filename = "programa-consolidado-" . now()->format('Y-m-d') . ".pdf";

        $pdf = Browsershot::html($html)
            //setNodeBinary(env('NODE_BINARY_PATH', '/usr/bin/node'))
            //setNpmBinary(env('NPM_BINARY_PATH', '/usr/bin/npm'))
            ->margins(10, 10, 10, 10)
            ->format('A4')
            ->showBackground()
            ->landscape()
            ->noSandbox()
            ->pdf();

        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}