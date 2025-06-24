<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Field;
use App\Models\Measure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class WiseconnService
{
    /**
     * Tipos de sensores permitidos según los modelos deseados.
     *
     * @var array
     */
    protected $allowedSensorTypes = [
        'Rain', 'Wind Velocity', 'Temperature', 'Humidity',
        'Chill Hours (Accumulated)', 'Chill Hours (Daily)',
        'Degree Days (Accumulated)', 'Degree Days (Daily)',
        'Et0', 'Etc',
    ];

    /**
     * Crea un cliente HTTP con Guzzle configurado para reintentos automáticos.
     *
     * @return Client
     */
    protected function createHttpClient(): Client
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(function ($retries, $request, $response, $exception) {
            return $retries < 3 && ($exception || ($response && $response->getStatusCode() >= 500));
        }));

        return new Client([
            'handler' => $stack,
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);
    }

    /**
     * Valida la clave API de un campo.
     *
     * @param Field $field
     * @return array
     */
    public function validateApiKey($field): array
    {
        Log::info("Validando api_key para el Field: {$field->name}");

        if (is_null($field->api_key)) {
            Log::warning("El Field {$field->name} no tiene una clave API configurada.");
            return ['valid' => false, 'message' => "El Field {$field->name} no tiene una clave API configurada."];
        }

        $client = $this->createHttpClient();
        try {
            $response = $client->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}", [
                'headers' => [
                    'api_key' => $field->api_key,
                    'Accept' => 'application/json',
                ],
            ]);

            Log::info("api_key válido para el Field: {$field->name}");
            return ['valid' => true, 'message' => 'Clave API válida.'];
        } catch (\Exception $e) {
            Log::error("Error al validar api_key para el Field: {$field->name}. Error: {$e->getMessage()}");
            return ['valid' => false, 'message' => "Error al validar clave API: {$e->getMessage()}"];
        }
    }

    /**
     * Sincroniza los datos de un campo desde la API de Wiseconn.
     *
     * @param Field $field
     * @return bool
     */
    public function syncFarmData(Field $field): bool
    {
        Log::info("Iniciando sincronización de coordenadas para el Campo ID: {$field->id}");

        if (is_null($field->wiseconn_farm_id) || is_null($field->api_key)) {
            Log::warning("No se puede sincronizar el Campo {$field->id} porque falta 'wiseconn_farm_id' o 'api_key'.");
            return false;
        }

        $client = $this->createHttpClient();
        try {
            $response = $client->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}", [
                'headers' => [
                    'api_key' => $field->api_key,
                    'Accept' => 'application/json',
                ],
            ]);

            $farmData = json_decode($response->getBody(), true);
            $field->latitude = $farmData['latitude'] ?? $field->latitude;
            $field->longitude = $farmData['longitude'] ?? $field->longitude;
            $field->saveQuietly();

            Log::info("Campo ID: {$field->id} actualizado con latitud: {$field->latitude} y longitud: {$field->longitude}.");
            return true;
        } catch (\Exception $e) {
            Log::error("Excepción al sincronizar el farm {$field->wiseconn_farm_id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Sincroniza las zonas de un campo desde la API.
     *
     * @param Field $field
     * @return void
     * @throws \Exception
     */
    public function syncZones($field): void
    {
        Log::info("Sincronizando zonas para el Field: {$field->name}");

        $validation = $this->validateApiKey($field);
        if (!$validation['valid']) {
            throw new \Exception($validation['message']);
        }

        $client = $this->createHttpClient();
        try {
            $response = $client->get("https://api.wiseconn.com/farms/{$field->wiseconn_farm_id}/zones", [
                'headers' => [
                    'api_key' => $field->api_key,
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'gzip, br, zstd',
                ],
            ]);

            $zonesData = json_decode($response->getBody(), true);
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
        } catch (\Exception $e) {
            Log::error("Error al sincronizar zonas: {$e->getMessage()}");
            throw new \Exception("Error al sincronizar zonas: {$e->getMessage()}");
        }
    }

    /**
     * Obtiene todas las medidas actuales de todas las zonas de un campo.
     *
     * @param Field $field
     * @return array
     */
    public function getAllZonesCurrentMeasures(Field $field): array
    {
        $cacheKey = "field_{$field->id}_all_zones_current_measures";
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($field) {
            $zones = Zone::where('field_id', $field->id)->get()->toArray();
            $allMeasures = $this->fetchAllZonesMeasures($field, $zones);
            $currentMeasures = [];

            foreach ($zones as $zone) {
                $zoneMeasures = $allMeasures[$zone['id']] ?? [];
                $zoneCurrentMeasures = [];
                foreach ($zoneMeasures as $measure) {
                    $sensorType = $this->getFilteredSensorType(
                        $measure['id'],
                        $measure['sensorType'] ?? 'unknown',
                        $zone['wiseconn_zone_id']
                    );

                    if (
                        $sensorType &&
                        in_array($sensorType, $this->allowedSensorTypes) &&
                        isset($measure['lastData']) &&
                        isset($measure['lastDataDate'])
                    ) {
                        $zoneCurrentMeasures[$sensorType] = [
                            'value' => $measure['lastData'],
                            'time' => $measure['lastDataDate'],
                        ];
                    }
                }
                $currentMeasures[$zone['id']] = $zoneCurrentMeasures;
            }

            return $currentMeasures;
        });
    }

    /**
     * Obtiene todas las medidas actuales de una zona específica.
     *
     * @param Field $field
     * @param Zone $zone
     * @return array
     */
    public function getAllCurrentMeasures(Field $field, Zone $zone): array
    {
        $cacheKey = "zone_{$zone->id}_current_measures";
        return Cache::remember($cacheKey, now()->addSeconds(300), function () use ($field, $zone) {
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
     * Obtiene medidas de una zona específica en un rango de tiempo.
     *
     * @param Field $field
     * @param Zone $zone
     * @param string $initTime
     * @param string $endTime
     * @return array
     */
    public function getZoneMeasures($field, Zone $zone, string $initTime, string $endTime): array
    {
        Log::info("Obteniendo medidas para la zona {$zone->name} del Field: {$field->name}");

        $validation = $this->validateApiKey($field);
        if (!$validation['valid']) {
            throw new \Exception($validation['message']);
        }

        $measures = $this->fetchZoneMeasures($field, $zone);
        return $this->fetchMeasuresData($field, $measures, $initTime, $endTime, $zone);
    }

    /**
     * Obtiene medidas de todas las zonas en paralelo usando Guzzle Pool.
     *
     * @param Field $field
     * @param array $zones
     * @return array
     */
    protected function fetchAllZonesMeasures(Field $field, array $zones): array
    {
        $client = $this->createHttpClient();
        $requests = function () use ($field, $zones, $client) {
            foreach ($zones as $zone) {
                yield new Request('GET', "https://api.wiseconn.com/zones/{$zone['wiseconn_zone_id']}/measures", [
                    'api_key' => $field->api_key,
                    'Accept' => 'application/json',
                ]);
            }
        };

        $measures = [];
        $pool = new Pool($client, $requests(), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) use ($zones, &$measures) {
                $zone = $zones[$index];
                $measures[$zone['id']] = json_decode($response->getBody(), true);
                Log::debug("Medidas obtenidas para la zona {$zone['name']}. Cantidad: " . count($measures[$zone['id']]));
            },
            'rejected' => function ($reason, $index) use ($zones) {
                $zone = $zones[$index];
                Log::error("Error al obtener medidas de la zona {$zone['name']}: {$reason}");
                $measures[$zone['id']] = [];
            },
        ]);

        $pool->promise()->wait();
        return $measures;
    }

    /**
     * Obtiene medidas de una zona específica desde la API.
     *
     * @param Field $field
     * @param Zone $zone
     * @return array
     */
    protected function fetchZoneMeasures($field, Zone $zone): array
    {
        $client = $this->createHttpClient();
        try {
            $response = $client->get("https://api.wiseconn.com/zones/{$zone->wiseconn_zone_id}/measures", [
                'headers' => [
                    'api_key' => $field->api_key,
                    'Accept' => 'application/json',
                ],
            ]);

            $measures = json_decode($response->getBody(), true);
            Log::debug("Medidas obtenidas para la zona {$zone->name}. Cantidad: " . count($measures));
            return $measures;
        } catch (\Exception $e) {
            Log::error("Error al obtener medidas de la zona {$zone->name}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Obtiene datos de medidas para un conjunto de medidas en un rango de tiempo.
     *
     * @param Field $field
     * @param array $measures
     * @param string $initTime
     * @param string $endTime
     * @param Zone $zone
     * @return array
     */
    protected function fetchMeasuresData($field, array $measures, string $initTime, string $endTime, Zone $zone): array
    {
        $client = $this->createHttpClient();
        $measuresData = [];

        foreach ($measures as $measure) {
            $measureId = $measure['id'];
            $sensorType = $this->getFilteredSensorType($measureId, $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);

            if ($sensorType === null) {
                Log::info("Medida ignorada: {$measureId} (sensorType: {$measure['sensorType']})");
                continue;
            }

            $cacheKey = $this->generateCacheKey($field->wiseconn_farm_id, $measureId, $initTime, $endTime);
            $measureDataResponse = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($client, $field, $measureId, $initTime, $endTime) {
                try {
                    $response = $client->get("https://api.wiseconn.com/measures/{$measureId}/data", [
                        'headers' => [
                            'api_key' => $field->api_key,
                            'Accept' => 'application/json',
                        ],
                        'query' => [
                            'initTime' => $initTime,
                            'endTime' => $endTime,
                            'tz' => 'UTC',
                        ],
                    ]);

                    return json_decode($response->getBody(), true);
                } catch (\Exception $e) {
                    Log::error("Fallo en la solicitud a la API para measure_id {$measureId}: {$e->getMessage()}");
                    return [];
                }
            });

            if (empty($measureDataResponse)) {
                Log::warning("No se encontraron datos para measure_id {$measureId} en el rango {$initTime} a {$endTime}");
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

        Log::info("Datos de medidas obtenidos exitosamente para la zona.");
        return $measuresData;
    }

    /**
     * Determina el tipo de sensor filtrado, incluyendo manejo para Et0/Etc.
     *
     * @param string $measureId
     * @param string $apiSensorType
     * @param string $wiseconnZoneId
     * @return ?string
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
     * Genera una clave única para el caché.
     *
     * @param string $farmId
     * @param string $measureId
     * @param string $initTime
     * @param string $endTime
     * @return string
     */
    protected function generateCacheKey(string $farmId, string $measureId, string $initTime, string $endTime): string
    {
        return "wiseconn_measure_{$farmId}_{$measureId}_" . md5($initTime . $endTime);
    }

    /**
     * Prepara datos para un gráfico de mediciones.
     *
     * @param Field $field
     * @param Zone $zone
     * @param string $initTime
     * @param string $endTime
     * @return array
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
                    $chartData['labels'] = array_map(function ($time) {
                        return Carbon::parse($time)->format('H:i');
                    }, array_column($dataPoints, 'time'));
                }

                $chartData['datasets'][] = $dataset;
                $index++;
            }
        }

        Log::info("Datos de gráfico preparados para la zona {$zone->name}");
        return $chartData;
    }

    /**
     * Obtiene la última medida disponible para un tipo de sensor específico.
     *
     * @param Field $field
     * @param Zone $zone
     * @param string $sensorType
     * @return array
     */
    public function getCurrentMeasures(Field $field, Zone $zone, string $sensorType): array
    {
        $initTime = Carbon::now('UTC')->subHours(2)->toIso8601String();
        $endTime = Carbon::now('UTC')->toIso8601String();

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
            return ['value' => null, 'time' => null];
        }

        $measureData = $this->fetchMeasuresData($field, [$targetMeasure], $initTime, $endTime, $zone);
        $dataPoints = $measureData[$sensorType][0]['data'] ?? [];

        if (!empty($dataPoints)) {
            $lastDataPoint = end($dataPoints);
            return [
                'value' => $lastDataPoint['value'] ?? null,
                'time' => $lastDataPoint['time'] ?? null,
            ];
        }

        return ['value' => null, 'time' => null];
    }

    /**
     * Obtiene el valor mínimo o máximo diario para un tipo de sensor.
     *
     * @param Field $field
     * @param Zone $zone
     * @param string $sensorType
     * @param string $initTime
     * @param string $endTime
     * @param string $type
     * @return ?float
     */
    public function getDailyMinMaxMeasure(Field $field, Zone $zone, string $sensorType, string $initTime, string $endTime, string $type = 'min'): ?float
    {
        $cacheKey = "zone_{$zone->id}_daily_{$type}_{$sensorType}_{$initTime}_{$endTime}";
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($field, $zone, $sensorType, $initTime, $endTime, $type) {
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
        });
    }

    /**
     * Obtiene la suma de valores para un tipo de sensor en un rango de tiempo.
     *
     * @param Field $field
     * @param Zone $zone
     * @param string $sensorType
     * @param string $initTime
     * @param string $endTime
     * @return ?float
     */
    public function getDailySumMeasure(Field $field, Zone $zone, string $sensorType, string $initTime, string $endTime): ?float
    {
        $cacheKey = "zone_{$zone->id}_sum_{$sensorType}_{$initTime}_{$endTime}";
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($field, $zone, $sensorType, $initTime, $endTime) {
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
        });
    }

    /**
     * Obtiene el valor acumulado para un tipo de sensor en un rango de tiempo.
     *
     * @param Field $field
     * @param Zone $zone
     * @param string $sensorType
     * @param string $initTime
     * @param string $endTime
     * @return ?float
     */
    public function getAccumulatedMeasure(Field $field, Zone $zone, string $sensorType, string $initTime, string $endTime): ?float
    {
        $cacheKey = "zone_{$zone->id}_accumulated_{$sensorType}_{$initTime}_{$endTime}";
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($field, $zone, $sensorType, $initTime, $endTime) {
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
        });
    }

    /**
     * Inicializa medidas históricas para una zona.
     *
     * @param Field $field
     * @param Zone $zone
     * @return bool
     */
    public function initializeHistoricalMeasures($field, Zone $zone): bool
    {
        if ($zone->is_historical_initialized) {
            Log::info("Medidas históricas ya inicializadas para la zona {$zone->name}");
            return true;
        }

        Log::info("Inicializando medidas históricas para la zona {$zone->name}");

        $startDateBase = Carbon::parse('2025-06-16T00:00:00Z', 'UTC');
        $currentLocalTime = Carbon::now('America/Santiago');
        $startDate = $startDateBase->isAfter($currentLocalTime) ? $currentLocalTime->startOfDay() : $startDateBase->timezone('America/Santiago');
        $endDate = Carbon::now('America/Santiago');
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

                $currentStart = $lastSavedTime ? Carbon::parse($lastSavedTime)->timezone('America/Santiago') : $startDate;

                if ($currentStart->greaterThanOrEqualTo($endDate)) {
                    continue;
                }

                $currentEnd = clone $currentStart;
                $currentEnd->addDays($maxDays);

                while ($currentStart->lessThan($endDate)) {
                    if ($currentEnd->greaterThan($endDate)) {
                        $currentEnd = clone $endDate;
                    }

                    $apiInitTime = $currentStart->toImmutable()->setTimezone('UTC')->toIso8601String();
                    $apiEndTime = $currentEnd->toImmutable()->setTimezone('UTC')->toIso8601String();

                    $measureDataForRange = $this->fetchMeasuresData($field, [$measure], $apiInitTime, $apiEndTime, $zone);
                    if (!empty($measureDataForRange)) {
                        $this->saveMeasures($zone, $measure, $measureDataForRange);
                    }

                    $currentStart = clone $currentEnd;
                    $currentEnd->addDays($maxDays);
                    usleep(500000); // Pausa de 0.5 segundos
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
     * Actualiza las medidas para una zona.
     *
     * @param Field $field
     * @param Zone $zone
     * @return bool
     */
    public function updateMeasures($field, Zone $zone): bool
    {
        Log::info("Actualizando medidas para la zona {$zone->name}");

        try {
            $measures = $this->fetchZoneMeasures($field, $zone);
            $endDate = Carbon::now('America/Santiago')->toIso8601String();

            foreach ($measures as $measure) {
                $measureId = $measure['id'];
                $lastUpdate = Measure::where('zone_id', $zone->id)
                    ->where('measure_id', $measureId)
                    ->max('time');

                $initTime = $lastUpdate
                    ? Carbon::parse($lastUpdate)->timezone('America/Santiago')->toIso8601String()
                    : Carbon::now('America/Santiago')->startOfDay()->toIso8601String();

                $measureData = $this->fetchMeasuresData($field, [$measure], $initTime, $endDate, $zone);
                $sensorType = $this->getFilteredSensorType($measure['id'], $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);

                if ($sensorType && !empty($measureData[$sensorType][0]['data'])) {
                    $this->saveMeasures($zone, $measure, $measureData);
                } else {
                    Log::info("No hay datos nuevos o el sensor no es relevante para measure_id: {$measureId}");
                }
            }

            Log::info("Medidas actualizadas para la zona {$zone->name}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error al actualizar medidas: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Guarda las medidas en la base de datos.
     *
     * @param Zone $zone
     * @param array $measure
     * @param array $measureData
     * @return void
     */
    public function saveMeasures(Zone $zone, array $measure, array $measureData): void
    {
        $sensorType = $this->getFilteredSensorType($measure['id'], $measure['sensorType'] ?? 'unknown', $zone->wiseconn_zone_id);

        if ($sensorType === null) {
            Log::warning("Medida ignorada al guardar: {$measure['id']} (sensorType: {$measure['sensorType']}) - No es un tipo permitido.");
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
                $time = Carbon::parse($dataPoint['time']);
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