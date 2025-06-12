<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Field;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;

class WiseconnService
{
    /**
     * Validar la clave API de un campo.
     *
     * @param mixed $field
     * @return array{valid: bool, message: string}
     */
    public function validateApiKey($field): array
    {
        Log::info("Validando api_key para el Field: {$field->name}");

        if (is_null($field->api_key)) {
            Log::warning("El Field {$field->name} no tiene una clave API configurada.");
            return ['valid' => false, 'message' => "El Field {$field->name} no tiene una clave API configurada."];
        }

        try {
            $response = Http::withHeaders([
                'api_key' => $field->api_key,
                'Accept' => 'application/json',
            ])->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}");

            if ($response->successful()) {
                Log::info("api_key válido para el Field: {$field->name}");
                return ['valid' => true, 'message' => 'Clave API válida.'];
            }

            Log::error("api_key inválido para el Field: {$field->name}. Respuesta: {$response->body()}");
            return ['valid' => false, 'message' => "Clave API inválida: {$response->body()}"];
        } catch (RequestException $e) {
            Log::error("Error al validar api_key para el Field: {$field->name}. Error: {$e->getMessage()}");
            return ['valid' => false, 'message' => "Error al validar clave API: {$e->getMessage()}"];
        }
    }

    /**
     * Sincroniza los datos de un campo (como latitud y longitud) desde la API de Wiseconn.
     *
     * @param Field $field El campo a sincronizar.
     * @return bool Devuelve true si la sincronización fue exitosa, false en caso contrario.
     */
    public function syncFarmData(Field $field): bool
    {
        Log::info("Iniciando sincronización de coordenadas para el Campo ID: {$field->id}");

        // Validamos que tengamos los datos necesarios para consultar la API
        if (is_null($field->wiseconn_farm_id) || is_null($field->api_key)) {
            Log::warning("No se puede sincronizar el Campo {$field->id} porque falta 'wiseconn_farm_id' o 'api_key'.");
            return false;
        }

        try {
            // Hacemos la llamada a la API para obtener los datos del farm específico
            $response = Http::withHeaders([
                'api_key' => $field->api_key,
                'Accept' => 'application/json',
            ])->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}");

            if ($response->successful()) {
                $farmData = $response->json();

                // Actualizamos nuestro modelo Field con los datos de la API
                $field->latitude = $farmData['latitude'] ?? $field->latitude;
                $field->longitude = $farmData['longitude'] ?? $field->longitude;
                $field->saveQuietly(); // Usamos saveQuietly para no disparar el evento 'saved' de nuevo y crear un bucle

                Log::info("Campo ID: {$field->id} actualizado con latitud: {$field->latitude} y longitud: {$field->longitude}.");
                return true;

            } else {
                Log::error("Error al obtener datos del farm {$field->wiseconn_farm_id}. Respuesta: " . $response->body());
                return false;
            }

        } catch (RequestException $e) {
            Log::error("Excepción al sincronizar el farm {$field->wiseconn_farm_id}: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Sincronizar las zonas de una granja desde la API.
     *
     * @param mixed $field
     * @throws \Exception
     */
    public function syncZones($field): void
    {
        Log::info("Sincronizando zonas para el Field: {$field->name}");

        $validation = $this->validateApiKey($field);
        if (!$validation['valid']) {
            Log::warning("Validación de api_key fallida: {$validation['message']}");
            throw new \Exception($validation['message']);
        }

        try {
            $response = Http::withHeaders([
                'api_key' => $field->api_key,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, br, zstd',
            ])->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}/zones");

            if (!$response->successful()) {
                Log::error("Error al sincronizar zonas: {$response->body()}");
                throw new \Exception("Error al sincronizar zonas: {$response->body()}");
            }

            $zonesData = $response->json();
            foreach ($zonesData as $zoneData) {
                Zone::updateOrCreate(
                    ['field_id' => $field->id, 'wiseconn_zone_id' => $zoneData['id']],
                    [
                        'name' => $zoneData['name'],
                        'description' => $zoneData['description'] ?? null,
                        'latitude' => $zoneData['latitude'],
                        'longitude' => $zoneData['longitude'],
                        'type' => $zoneData['type'],
                    ]
                );
            }

            Log::info("Zonas sincronizadas exitosamente para el Field: {$field->name}");
        } catch (RequestException $e) {
            Log::error("Error al sincronizar zonas: {$e->getMessage()}");
            throw new \Exception("Error al sincronizar zonas: {$e->getMessage()}");
        }
    }

    /**
     * Obtener las zonas de tipo Weather de un campo.
     *
     * @param mixed $field
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWeatherZones($field)
    {
        Log::info("Obteniendo zonas de tipo Weather para el Field: {$field->name}");

        $zones = Zone::where('field_id', $field->id)
            ->whereJsonContains('type', 'Weather')
            ->get();

        Log::debug("Zonas obtenidas desde la base de datos. Cantidad: {$zones->count()}");
        return $zones;
    }

    /**
     * Obtener todas las medidas de una zona específica.
     *
     * @param mixed $field
     * @param Zone $zone
     * @param string $initTime
     * @param string $endTime
     * @return array
     * @throws \Exception
     */
    public function getZoneMeasures($field, Zone $zone, string $initTime, string $endTime): array
    {
        Log::info("Obteniendo medidas para la zona {$zone->name} del Field: {$field->name}");

        $validation = $this->validateApiKey($field);
        if (!$validation['valid']) {
            Log::warning("Validación de api_key fallida: {$validation['message']}");
            throw new \Exception($validation['message']);
        }

        $measures = $this->fetchZoneMeasures($field, $zone);
        return $this->fetchMeasuresData($field, $measures, $initTime, $endTime);
    }

    /**
     * Obtener las medidas de una zona desde la API.
     *
     * @param mixed $field
     * @param Zone $zone
     * @return array
     * @throws \Exception
     */
    protected function fetchZoneMeasures($field, Zone $zone): array
    {
        try {
            $response = Http::withHeaders([
                'api_key' => $field->api_key,
                'Accept' => 'application/json',
            ])->get("https://api.wiseconn.com/zones/{$zone->wiseconn_zone_id}/measures");

            if (!$response->successful()) {
                Log::error("Error al obtener medidas de la zona {$zone->name}: {$response->body()}");
                throw new \Exception("Error al obtener medidas de la zona: {$response->body()}");
            }

            $measures = $response->json();
            Log::debug("Medidas obtenidas para la zona {$zone->name}. Cantidad: " . count($measures));
            return $measures;
        } catch (RequestException $e) {
            Log::error("Error al obtener medidas de la zona {$zone->name}: {$e->getMessage()}");
            throw new \Exception("Error al obtener medidas de la zona: {$e->getMessage()}");
        }
    }

    /**
     * Obtener los datos de las medidas en un rango de tiempo.
     *
     * @param mixed $field
     * @param array $measures
     * @param string $initTime
     * @param string $endTime
     * @return array
     * @throws \Exception
     */
    protected function fetchMeasuresData($field, array $measures, string $initTime, string $endTime): array
    {
        $measuresData = [];

        foreach ($measures as $measure) {
            $measureId = $measure['id'];
            $cacheKey = $this->generateCacheKey($field->wiseconn_farm_id, $measureId, $initTime, $endTime);

            $measureData = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($field, $measureId, $initTime, $endTime) {
                try {
                    Log::info("Obteniendo datos para la medida {$measureId}");
                    $response = Http::withHeaders([
                        'api_key' => $field->api_key,
                        'Accept' => 'application/json',
                    ])->get("https://api.wiseconn.com/measures/{$measureId}/data", [
                        'initTime' => $initTime,
                        'endTime' => $endTime,
                        'tz' => 'UTC',
                    ]);

                    if (!$response->successful()) {
                        Log::error("Error al obtener datos de la medida {$measureId}: {$response->body()}");
                        throw new \Exception("Error al obtener datos de la medida {$measureId}: {$response->body()}");
                    }

                    return $response->json();
                } catch (RequestException $e) {
                    Log::error("Error al obtener datos de la medida {$measureId}: {$e->getMessage()}");
                    throw new \Exception("Error al obtener datos de la medida: {$e->getMessage()}");
                }
            });

            $measuresData[$measure['sensorType']][] = [
                'id' => $measureId,
                'name' => $measure['name'],
                'unit' => $measure['unit'],
                'lastData' => $measure['lastData'],
                'lastDataDate' => $measure['lastDataDate'],
                'data' => $measureData,
            ];
        }

        Log::info("Datos de medidas obtenidos exitosamente.");
        return $measuresData;
    }

    /**
     * Generar una clave única para el caché.
     *
     * @param string $farmId
     * @param string $measureId
     * @param string $initTime
     * @param string $endTime
     * @return string
     */
    protected function generateCacheKey(string $farmId, string $measureId, string $initTime, string $endTime): string
    {
        return "wiseconn_measure_{$farmId}_{$measureId}_{$initTime}_{$endTime}";
    }
    /**
     * Preparar datos para un gráfico de mediciones.
     *
     * @param mixed $field
     * @param Zone $zone
     * @param string $initTime
     * @param string $endTime
     * @return array
     * @throws \Exception
     */
    public function getChartData($field, Zone $zone, string $initTime, string $endTime): array
    {
        Log::info("Preparando datos de gráfico para la zona {$zone->name}");

        $measures = $this->getZoneMeasures($field, $zone, $initTime, $endTime);

        $chartData = [
            'labels' => [],
            'datasets' => [],
        ];

        $colors = ['#3b82f6', '#9333ea', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'];

        $index = 0;
        foreach ($measures as $sensorType => $measureData) {
            foreach ($measureData as $measure) {
                $dataPoints = $measure['data'];
                $dataset = [
                    'label' => "{$measure['name']} ({$measure['unit']})",
                    'data' => array_column($dataPoints, 'value'),
                    'borderColor' => $colors[$index % count($colors)],
                    'backgroundColor' => "rgba(" . hexToRgb($colors[$index % count($colors)]) . ", 0.2)",
                    'fill' => true,
                ];

                if (empty($chartData['labels'])) {
                    $chartData['labels'] = array_column($dataPoints, 'time');
                }

                $chartData['datasets'][] = $dataset;
                $index++;
            }
        }

        Log::info("Datos de gráfico preparados para la zona {$zone->name}");
        return $chartData;
    }
}

// Función auxiliar para convertir hex a RGB
if (!function_exists('hexToRgb')) {
    function hexToRgb($hex) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }
}

