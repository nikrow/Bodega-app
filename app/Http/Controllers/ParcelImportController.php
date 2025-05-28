<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ParcelImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParcelImportController extends Controller
{
    /**
     * Manejar la importación de cuarteles.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        // Iniciar una transacción
        DB::beginTransaction();

        try {
            // Crear instancia del importador
            Log::info('Iniciando importación');
            $import = new ParcelImport();
            Excel::import($import, $request->file('file'));

            // Obtener los IDs de los predios involucrados
            $fieldIds = collect($import->getProcessedParcels())
                ->pluck('field_id')
                ->unique()
                ->toArray();

            // Desactivar parcelas que no están en el archivo
            $import->deactivateMissingParcels($fieldIds);

            // Confirmar la transacción
            DB::commit();

            // Obtener el resumen
            $summary = $import->getSummary();

            return response()->json([
                'message' => 'Cuarteles importados exitosamente.',
                'summary' => $summary,
            ], 200);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            Log::error("Error durante la importación: " . $e->getMessage());
            return response()->json(['error' => 'Error durante la importación.'], 500);
        }
    }
}