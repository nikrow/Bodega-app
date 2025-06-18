<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Field;
use App\Services\WiseconnService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request; // Importa Request

class WiseconnController extends Controller
{
    public function initializeData()
    {
        $field = Field::first(); // Ajusta según tu lógica para obtener el campo correcto
        if (!$field) {
            return response()->json(['error' => 'No se encontró ningún campo.'], 404);
        }

        // Forzar sincronización de zonas
        (new WiseconnService())->syncZones($field);

        // Obtener todas las zonas (ya que todas son Weather por diseño)
        $zones = Zone::where('field_id', $field->id)->get();
        foreach ($zones as $zone) {
            (new WiseconnService())->initializeHistoricalMeasures($field, $zone);
        }

        return response()->json(['message' => 'Inicializacion de datos historicos completada.']);
    }

    /**
     * Muestra una lista de zonas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function showZones(Request $request)
    {
        $field = Field::first(); // Puedes ajustar esto para seleccionar un campo específico si es necesario.

        if (!$field) {
            return response()->json(['error' => 'No se encontró ningún campo para mostrar las zonas.'], 404);
        }

        try {
            // Sincronizar zonas antes de mostrarlas para asegurar datos actualizados
            (new WiseconnService())->syncZones($field);

            // Obtener todas las zonas para el campo
            $zones = Zone::where('field_id', $field->id)->get();

            return view('zones.index', compact('zones', 'field'));
        } catch (\Exception $e) {
            // Loguear el error para depuración
            Log::error("Error al cargar las zonas: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Hubo un error al cargar las zonas. Por favor, inténtalo de nuevo más tarde.'], 500);
        }
    }
}