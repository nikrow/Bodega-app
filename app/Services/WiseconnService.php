<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Field;
use App\Models\Measure;
use App\Models\ZoneSummary;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;

class WiseconnService
{
    protected $allowedSensorTypes = [
        'Rain',
        'Wind Velocity',
        'Temperature',
        'Humidity',
        'Chill Hours (Accumulated)',
        'Chill Hours (Daily)',
        'Degree Days (Accumulated)',
        'Degree Days (Daily)',
        'Et0',
        'Etc',
    ];

    /**
     * Valida la clave API de un campo.
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
     */
    public function syncFarmData(Field $field): bool
    {
        Log::info("Iniciando sincronización de coordenadas para el Campo ID: {$field->id}");

        if (is_null($field->wiseconn_farm_id) || is_null($field->api_key)) {
            Log::warning("No se puede sincronizar el Campo {$field->id} porque falta 'wiseconn_farm_id' o 'api_key'.");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'api_key' => $field->api_key,
                'Accept' => 'application/json',
            ])->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}");

            if ($response->successful()) {
                $farmData = $response->json();

                $field->latitude = $farmData['latitude'] ?? $field->latitude;
                $field->longitude = $farmData['longitude'] ?? $field->longitude;
                $field->saveQuietly();

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
                if (in_array('Weather', (array) $zoneData['type'])) {
                    Zone::updateOrCreate(
                        ['field_id' => $field->id, 'wiseconn_zone_id' => $zoneData['id']],
                        [
                            'name' => $zoneData['name'],
                            'description' => $zoneData['description'] ?? null,
                            'latitude' => $zoneData['latitude'],
                            'longitude' => $zoneData['longitude'],
                            'type' => json_encode(['Weather']),
                        ]
                    );
                }
            }
            Log::info("Zonas de tipo Weather sincronizadas exitosamente para el Field: {$field->name}");
        } catch (RequestException $e) {
            Log::error("Error al sincronizar zonas: {$e->getMessage()}");
            throw new \Exception("Error al sincronizar zonas: {$e->getMessage()}");
        }
    }

    /**
     * Obtener las medidas de una zona desde la API.
     */
    protected function fetchZoneMeasures($field, Zone $zone): array
    {
        Log::info("Intentando obtener medidas para la zona: {$zone->name} (Wiseconn Zone ID: {$zone->wiseconn_zone_id})");
        try {
            $response = Http::withHeaders([
                'api_key' => $field->api_key,
                'Accept' => 'application/json',
            ])->get("https://api.wiseconn.com/zones/{$zone->wiseconn_zone_id}/measures", [
                'tz' => 'UTC',
            ]);

            if (!$response->successful()) {
                Log::error("Error al obtener medidas de la zona {$zone->name} (ID: {$zone->wiseconn_zone_id}). Código de estado: {$response->status()}. Respuesta: {$response->body()}");
                throw new \Exception("Error al obtener medidas de la zona: {$response->body()}");
            }

            $measures = $response->json();
            if (empty($measures)) {
                Log::warning("La API de Wiseconn devolvió un array de medidas vacío para la zona {$zone->name} (ID: {$zone->wiseconn_zone_id}).");
            } else {
                Log::info("Medidas obtenidas exitosamente para la zona {$zone->name}. Cantidad: " . count($measures));
            }
            return $measures;
        } catch (\Exception $e) {
            Log::error("Excepción al obtener medidas de la zona {$zone->name} (ID: {$zone->wiseconn_zone_id}): {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Obtener datos de medidas para un conjunto de medidas en un rango de tiempo.
     */
    protected function fetchMeasuresData($field, array $measures, string $initTime, string $endTime, Zone $zone): array
    {
        Log::info("Iniciando fetchMeasuresData para la zona: {$zone->name} desde {$initTime} hasta {$endTime}. Medidas a procesar: " . count($measures));
        try {
            $measuresData = [];

            foreach ($measures as $measure) {
                $measureId = $measure['id'];
                $sensorType = $this->getFilteredSensorType($measureId, $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);

                if ($sensorType === null || !in_array('Weather', json_decode($zone->type, true))) {
                    Log::debug("Medida ignorada en fetchMeasuresData: {$measureId} (sensorType: {$measure['sensorType']}). No es un tipo de sensor permitido o la zona no es 'Weather'.");
                    continue;
                }

                $cacheKey = $this->generateCacheKey($field->wiseconn_farm_id, $measureId, $initTime, $endTime);

                // Cambiar el tiempo de caché para pruebas, o considerar si el caché está impidiendo nuevas solicitudes
                $measureDataResponse = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($field, $measureId, $initTime, $endTime) {
                    try {
                        Log::info("Realizando solicitud a la API para medida {$measureId} de {$initTime} a {$endTime}. (Desde caché closure)");
                        $response = Http::withHeaders([
                            'api_key' => $field->api_key,
                            'Accept' => 'application/json',
                        ])->get("https://api.wiseconn.com/measures/{$measureId}/data", [
                            'initTime' => $initTime,
                            'endTime' => $endTime,
                            'tz' => 'UTC',
                        ]);

                        if (!$response->successful()) {
                            Log::error("Fallo en la solicitud a la API para measure_id {$measureId} (Rango: {$initTime} a {$endTime}). Código de estado: {$response->status()}. Respuesta: {$response->body()}");
                            throw new RequestException($response);
                        }

                        $data = $response->json();
                        Log::info("Datos de API obtenidos para medida {$measureId}. Cantidad de puntos: " . count($data));
                        return $data;
                    } catch (RequestException $e) {
                        Log::error("Excepción en la solicitud a la API para measure_id {$measureId}: {$e->getMessage()}");
                        return [];
                    }
                });

                if (empty($measureDataResponse)) {
                    Log::warning("No se encontraron datos (o la API devolvió vacío) para measure_id {$measureId} en el rango {$initTime} a {$endTime}. CacheKey: {$cacheKey}");
                    continue;
                }

                if (!isset($measuresData[$sensorType])) {
                    $measuresData[$sensorType] = [];
                }

                $measuresData[$sensorType][] = [
                    'id' => $measureId,
                    'name' => $measure['name'],
                    'unit' => $measure['unit'],
                    'data' => $measureDataResponse,
                ];
            }

            Log::info("fetchMeasuresData completado para la zona {$zone->name}. Sensores procesados: " . count($measuresData));
            return $measuresData;
        } catch (\Exception $e) {
            Log::error("Error en fetchMeasuresData para la zona {$zone->name}: {$e->getMessage()}");
            throw $e; 
        }
    }

    /**
     * Determina el tipo de sensor filtrado, incluyendo manejo para Et0/Etc.
     */
    protected function getFilteredSensorType(string $measureId, string $apiSensorType, string $wiseconnZoneId): ?string
    {
        if (in_array($apiSensorType, $this->allowedSensorTypes)) {
            return $apiSensorType;
        }

        $measureIdParts = explode('-', $measureId);
        if (count($measureIdParts) === 3 && $measureIdParts[0] === '6' && $measureIdParts[1] === (string)$wiseconnZoneId) {
            if ($measureIdParts[2] === '1') {
                return 'Et0';
            } elseif ($measureIdParts[2] === '2') {
                return 'Etc';
            }
        }

        Log::warning("Measure ID {$measureId} no coincide con ningún tipo de sensor permitido.");
        return null;
    }

    /**
     * Generar una clave única para el caché.
     */
    protected function generateCacheKey(string $farmId, string $measureId, string $initTime, string $endTime): string
    {
        return "wiseconn_measure_{$farmId}_{$measureId}_" . md5($initTime . $endTime);
    }

    /**
     * Actualizar medidas actuales y diarias en zone_summaries.
     */
    public function updateZoneSummary(Field $field, Zone $zone): void
    {
        if (!in_array('Weather', json_decode($zone->type, true))) {
            Log::info("Zona {$zone->name} no es de tipo Weather, omitiendo actualización de zone_summaries.");
            return;
        }

        try {
            // Obtener medidas actuales
            $currentMeasures = $this->getAllCurrentMeasures($field, $zone);

            // Preparar datos para zone_summaries
            $summaryData = [
                'zone_id' => $zone->id,
                'current_temperature' => $currentMeasures['Temperature']['value'] ?? null,
                'current_temperature_time' => isset($currentMeasures['Temperature']['time']) ? Carbon::parse($currentMeasures['Temperature']['time'], 'UTC') : null,
                'current_humidity' => $currentMeasures['Humidity']['value'] ?? null,
                'current_humidity_time' => isset($currentMeasures['Humidity']['time']) ? Carbon::parse($currentMeasures['Humidity']['time'], 'UTC') : null,
                'chill_hours_accumulated' => $currentMeasures['Chill Hours (Accumulated)']['value'] ?? null,
                'chill_hours_accumulated_time' => isset($currentMeasures['Chill Hours (Accumulated)']['time']) ? Carbon::parse($currentMeasures['Chill Hours (Accumulated)']['time'], 'UTC') : null,
            ];

            // Obtener medidas diarias
            $today = Carbon::now('America/Santiago');
            $initTime = $today->startOfDay()->toIso8601String();
            $endTime = $today->endOfDay()->toIso8601String();

            // Temperatura mínima
            $minTempData = $this->getDailyMinMaxMeasure($field, $zone, 'Temperature', $initTime, $endTime, 'min');
            if ($minTempData !== null) {
                $summaryData['min_temperature_daily'] = $minTempData;
                $summaryData['min_temperature_time'] = $today;
            }

            // Temperatura máxima
            $maxTempData = $this->getDailyMinMaxMeasure($field, $zone, 'Temperature', $initTime, $endTime, 'max');
            if ($maxTempData !== null) {
                $summaryData['max_temperature_daily'] = $maxTempData;
                $summaryData['max_temperature_time'] = $today;
            }

            // Lluvia diaria
            $dailyRain = $this->getDailySumMeasure($field, $zone, 'Rain', $initTime, $endTime);
            if ($dailyRain !== null) {
                $summaryData['daily_rain'] = $dailyRain;
                $summaryData['daily_rain_time'] = $today;
            }

            // Horas frío diarias
            $dailyChillHours = $this->getDailySumMeasure($field, $zone, 'Chill Hours (Daily)', $initTime, $endTime);
            if ($dailyChillHours !== null) {
                $summaryData['chill_hours_daily'] = $dailyChillHours;
                $summaryData['chill_hours_daily_time'] = $today;
            }

            // Actualizar o crear el resumen de la zona
            ZoneSummary::updateOrCreate(
                ['zone_id' => $zone->id],
                $summaryData
            );

            Log::info("Resumen de medidas actualizado para la zona {$zone->name}");
        } catch (\Exception $e) {
            Log::error("Error al actualizar zone_summaries para la zona {$zone->name}: {$e->getMessage()}");
        }
    }

    /**
     * Actualizar historial de medidas en measures.
     */
    public function updateHistoricalMeasures(Field $field, Zone $zone): void
    {
        if (!in_array('Weather', json_decode($zone->type, true))) {
            Log::info("Zona {$zone->name} no es de tipo Weather, omitiendo actualización de medidas históricas.");
            return;
        }

        Log::info("Iniciando actualización de historial de medidas para la zona {$zone->name} (Field ID: {$field->id}, Zone ID: {$zone->id}).");

        try {
            $measures = $this->fetchZoneMeasures($field, $zone);
            if (empty($measures)) {
                Log::warning("No se encontraron medidas para la zona {$zone->name}. No se puede actualizar el historial.");
                return;
            }

            // Define el rango de tiempo para la actualización.
            // Para "cada minuto", es razonable buscar datos de los últimos 15-30 minutos para no perder nada.
            $endTime = Carbon::now('UTC');
            $initTime = (clone $endTime)->subMinutes(30); 

            foreach ($measures as $measure) {
                $measureId = $measure['id'];
                $sensorType = $this->getFilteredSensorType($measureId, $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);

                if ($sensorType === null) {
                    Log::debug("Saltando medida {$measureId} en updateHistoricalMeasures: tipo de sensor no permitido o desconocido.");
                    continue;
                }

                $lastUpdate = Measure::where('zone_id', $zone->id)
                    ->where('measure_id', $measureId)
                    ->max('time');

                $fetchInitTime = $lastUpdate ? Carbon::parse($lastUpdate, 'UTC')->addSecond() : $initTime;
                $fetchInitTime = $fetchInitTime->greaterThan($initTime) ? $fetchInitTime : $initTime; // Asegurar que no se vaya al futuro

                if ($fetchInitTime->greaterThanOrEqualTo($endTime)) {
                    Log::info("No hay datos nuevos a buscar para measure_id {$measureId} en el rango actual (última actualización: {$lastUpdate}, rango: {$initTime->toIso8601String()} - {$endTime->toIso8601String()}).");
                    continue;
                }
                
                $measureData = $this->fetchMeasuresData($field, [$measure], $fetchInitTime->toIso8601String(), $endTime->toIso8601String(), $zone);
                
                if (empty($measureData[$sensorType][0]['data'])) {
                    Log::info("No se encontraron nuevos puntos de datos para measure_id: {$measureId} en el rango {$fetchInitTime->toIso8601String()} a {$endTime->toIso8601String()}.");
                    continue;
                }

                $dataArray = $measureData[$sensorType][0]['data'];
                $dataToInsert = [];

                foreach ($dataArray as $dataPoint) {
                    if (isset($dataPoint['time']) && $dataPoint['time'] !== null && isset($dataPoint['value'])) {
                        $time = Carbon::parse($dataPoint['time'], 'UTC');
                        // Se omite la verificación de $lastUpdate aquí ya que $fetchInitTime ya la maneja,
                        // y la verificación de 'exists' se hace para evitar duplicados estrictos.
                        $exists = Measure::where('zone_id', $zone->id)
                            ->where('measure_id', $measureId)
                            ->where('time', $time)
                            ->exists();

                        if (!$exists) {
                            $dataToInsert[] = [
                                'zone_id' => $zone->id,
                                'measure_id' => $measureId,
                                'name' => $measure['name'],
                                'unit' => $measure['unit'],
                                'value' => $dataPoint['value'],
                                'time' => $time,
                                'sensor_type' => $sensorType,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    } else {
                        Log::warning("Dato de API incompleto para measure_id {$measureId}: " . json_encode($dataPoint));
                    }
                }

                if (!empty($dataToInsert)) {
                    Measure::insert($dataToInsert);
                    Log::info("Guardados " . count($dataToInsert) . " nuevos registros históricos para measure_id {$measureId} en la zona {$zone->name}.");
                } else {
                    Log::info("No hay nuevos registros únicos para insertar para measure_id {$measureId} en la zona {$zone->name}.");
                }
            }

            Log::info("Historial de medidas actualizado para la zona {$zone->name} completado.");
        } catch (\Exception $e) {
            Log::error("Error general al actualizar historial de medidas para la zona {$zone->name}: {$e->getMessage()} en línea {$e->getLine()} de {$e->getFile()}.");
        }
    }

    /**
     * Obtener todas las medidas actuales para una zona.
     */
    public function getAllCurrentMeasures(Field $field, Zone $zone): array
    {
        if (!in_array('Weather', json_decode($zone->type, true))) {
            return [];
        }

        $cacheKey = "zone_{$zone->id}_current_measures";

        return Cache::remember($cacheKey, 600, function () use ($field, $zone) {
            $measures = $this->fetchZoneMeasures($field, $zone);
            $currentMeasures = [];

            foreach ($measures as $measure) {
                $sensorType = $this->getFilteredSensorType(
                    $measure['id'],
                    $measure['sensorType'] ?? 'unknown',
                    $zone->wiseconn_zone_id
                );

                if (
                    $sensorType &&
                    in_array($sensorType, $this->allowedSensorTypes) &&
                    isset($measure['lastData']) &&
                    isset($measure['lastDataDate'])
                ) {
                    $currentMeasures[$sensorType] = [
                        'value' => $measure['lastData'],
                        'time' => $measure['lastDataDate'],
                    ];
                }
            }

            return $currentMeasures;
        });
    }

    /**
     * Obtiene el valor mínimo o máximo para un tipo de sensor en un rango de tiempo.
     */
    public function getDailyMinMaxMeasure(Field $field, Zone $zone, string $sensorType, string $initTime, string $endTime, string $type = 'min'): ?float
    {
        try {
            $measures = $this->fetchZoneMeasures($field, $zone);
            $targetMeasure = null;

            foreach ($measures as $measure) {
                $filteredType = $this->getFilteredSensorType($measure['id'], $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);
                if ($filteredType === $sensorType) {
                    $targetMeasure = $measure;
                    break;
                }
            }

            if (!$targetMeasure) {
                return null;
            }

            $measureData = $this->fetchMeasuresData($field, [$targetMeasure], $initTime, $endTime, $zone);
            $dataPoints = $measureData[$sensorType][0]['data'] ?? [];

            if (empty($dataPoints)) {
                Log::warning("No hay datos para '{$sensorType}' en el rango {$initTime} a {$endTime}");
                return null;
            }

            $values = array_column($dataPoints, 'value');
            return $type === 'min' ? min($values) : max($values);
        } catch (\Exception $e) {
            Log::error("Error al obtener {$type} de '{$sensorType}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Obtiene la suma de valores para un tipo de sensor en un rango de tiempo.
     */
    public function getDailySumMeasure(Field $field, Zone $zone, string $sensorType, string $initTime, string $endTime): ?float
    {
        try {
            $measures = $this->fetchZoneMeasures($field, $zone);
            $targetMeasure = null;

            foreach ($measures as $measure) {
                $filteredType = $this->getFilteredSensorType($measure['id'], $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);
                if ($filteredType === $sensorType) {
                    $targetMeasure = $measure;
                    break;
                }
            }

            if (!$targetMeasure) {
                return null;
            }

            $measureData = $this->fetchMeasuresData($field, [$targetMeasure], $initTime, $endTime, $zone);
            $dataPoints = $measureData[$sensorType][0]['data'] ?? [];

            if (empty($dataPoints)) {
                Log::warning("No hay datos para '{$sensorType}' en el rango {$initTime} a {$endTime}");
                return null;
            }

            $values = array_column($dataPoints, 'value');
            return array_sum($values);
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma de '{$sensorType}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Obtiene el valor acumulado para un tipo de sensor en un rango de tiempo.
     */
    public function getAccumulatedMeasure(Field $field, Zone $zone, string $sensorType, string $initTime, string $endTime): ?float
    {
        try {
            $measures = $this->fetchZoneMeasures($field, $zone);
            $targetMeasure = null;

            foreach ($measures as $measure) {
                $filteredType = $this->getFilteredSensorType($measure['id'], $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);
                if ($filteredType === $sensorType) {
                    $targetMeasure = $measure;
                    break;
                }
            }

            if (!$targetMeasure) {
                return null;
            }

            $measureData = $this->fetchMeasuresData($field, [$targetMeasure], $initTime, $endTime, $zone);
            $dataPoints = $measureData[$sensorType][0]['data'] ?? [];

            if (empty($dataPoints)) {
                return null;
            }

            $lastDataPoint = end($dataPoints);
            return $lastDataPoint['value'] ?? null;
        } catch (\Exception $e) {
            Log::error("Error al obtener el acumulado de '{$sensorType}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Inicializa medidas históricas para una zona.
     */
    public function initializeHistoricalMeasures($field, Zone $zone): bool
    {
        if ($zone->is_historical_initialized) {
            Log::info("Medidas históricas ya inicializadas para la zona {$zone->name}");
            return true;
        }

        Log::info("Inicializando medidas históricas para la zona {$zone->name}");

        $startDateBase = Carbon::parse('2025-06-16T00:00:00Z', 'UTC');
        $currentLocalTime = Carbon::now('UTC');
        $startDate = $startDateBase->isAfter($currentLocalTime) ? $currentLocalTime->startOfDay() : $startDateBase;
        $endDate = Carbon::now('UTC');
        $maxDays = 30;

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1024M');

        try {
            $measures = $this->fetchZoneMeasures($field, $zone);
            foreach ($measures as $measure) {
                $measureId = $measure['id'];
                $lastSavedTime = Measure::where('zone_id', $zone->id)
                    ->where('measure_id', $measureId)
                    ->max('time');

                $currentStart = $lastSavedTime ? Carbon::parse($lastSavedTime, 'UTC') : $startDate;

                if ($currentStart->greaterThanOrEqualTo($endDate)) {
                    continue;
                }

                $currentEnd = clone $currentStart;
                $currentEnd->addDays($maxDays);

                while ($currentStart->lessThan($endDate)) {
                    if ($currentEnd->greaterThan($endDate)) {
                        $currentEnd = clone $endDate;
                    }

                    $apiInitTime = $currentStart->toIso8601String();
                    $apiEndTime = $currentEnd->toIso8601String();

                    $measureDataForRange = $this->fetchMeasuresData($field, [$measure], $apiInitTime, $apiEndTime, $zone);
                    if (!empty($measureDataForRange)) {
                        $this->saveMeasures($zone, $measure, $measureDataForRange);
                    }

                    $currentStart = clone $currentEnd;
                    $currentEnd->addDays($maxDays);
                    usleep(500000);
                }
            }

            $zone->update(['is_historical_initialized' => true]);
            return true;
        } catch (\Throwable $t) {
            Log::error("Error al inicializar medidas históricas: {$t->getMessage()}");
            return false;
        }
    }

    /**
     * Guarda las medidas en la base de datos.
     */
    public function saveMeasures(Zone $zone, array $measure, array $measureData): void
    {
        try {
            $sensorType = $this->getFilteredSensorType($measure['id'], $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);

            if ($sensorType === null || !in_array('Weather', json_decode($zone->type, true))) {
                Log::warning("Medida ignorada al guardar: {$measure['id']} (sensorType: {$measure['sensorType']})");
                return;
            }

            if (!isset($measureData[$sensorType])) {
                Log::warning("No se encontraron datos para el sensorType '{$sensorType}' en measureData para measure_id {$measure['id']}.");
                return;
            }

            $dataArray = $measureData[$sensorType][0]['data'] ?? null;
            if (empty($dataArray)) {
                Log::warning("No se encontraron datos históricos para measure_id {$measure['id']} al intentar guardar.");
                return;
            }

            $dataToInsert = [];
            foreach ($dataArray as $dataPoint) {
                if (isset($dataPoint['time']) && $dataPoint['time'] !== null && isset($dataPoint['value'])) {
                    $time = Carbon::parse($dataPoint['time'], 'UTC');
                    $exists = Measure::where('zone_id', $zone->id)
                        ->where('measure_id', $measure['id'])
                        ->where('time', $time)
                        ->exists();
                    if (!$exists) {
                        $dataToInsert[] = [
                            'zone_id' => $zone->id,
                            'measure_id' => $measure['id'],
                            'name' => $measure['name'],
                            'unit' => $measure['unit'],
                            'value' => $dataPoint['value'],
                            'time' => $time,
                            'sensor_type' => $sensorType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                } else {
                    Log::warning("Dato omitido para measure_id {$measure['id']} debido a time o value nulo al guardar: " . json_encode($dataPoint));
                }
            }

            if (!empty($dataToInsert)) {
                Measure::insert($dataToInsert);
                Log::info("Guardados " . count($dataToInsert) . " nuevos registros para measure_id {$measure['id']}");
            } else {
                Log::info("No hay nuevos registros para guardar para measure_id {$measure['id']}");
            }
        } catch (\Exception $e) {
            Log::error("Error al guardar medidas para measure_id {$measure['id']}: {$e->getMessage()}");
            throw $e;
        }
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