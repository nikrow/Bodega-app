<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Services\WiseconnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class ZoneSyncController extends Controller
{
    protected WiseconnService $wiseconnService;

    public function __construct(WiseconnService $wiseconnService)
    {
        $this->wiseconnService = $wiseconnService;
    }

    /**
     * Sincronizar las zonas de un campo específico.
     *
     * @param Field $field
     * @return JsonResponse
     */
    public function sync(Field $field): JsonResponse
    {
        try {
            // Llamar al método syncZones del servicio
            $this->wiseconnService->syncZones($field);

            // Notificación de éxito para Filament
            Notification::make()
                ->title('Éxito')
                ->body("Las zonas del campo {$field->name} se sincronizaron correctamente.")
                ->success()
                ->send();

            return response()->json([
                'success' => true,
                'message' => "Zonas sincronizadas exitosamente para el campo {$field->name}.",
            ], 200);
        } catch (\Exception $e) {
            // Registrar el error
            Log::error("Error al sincronizar zonas para el campo {$field->name}: {$e->getMessage()}");

            // Notificación de error para Filament
            Notification::make()
                ->title('Error')
                ->body("No se pudieron sincronizar las zonas: {$e->getMessage()}")
                ->danger()
                ->send();

            return response()->json([
                'success' => false,
                'message' => "Error al sincronizar zonas: {$e->getMessage()}",
            ], 500);
        }
    }
}