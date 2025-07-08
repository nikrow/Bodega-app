<?php

namespace App\Jobs;

use App\Models\Field;
use App\Services\WiseconnService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheClimateDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WiseconnService $wiseconnService): void
    {
        Log::info('Iniciando caché de datos climáticos para todas las fields.');

        $fields = Field::all();
        foreach ($fields as $field) {
            $cacheKey = "field_{$field->id}_latest_climate_values";
            Cache::remember($cacheKey, now()->addMinutes(30), function () use ($field, $wiseconnService) {
                Log::info("Cacheando datos climáticos para Field ID: {$field->id}");
                $latestValues = [
                    'Wind Velocity' => ['value' => null, 'time' => null],
                    'Temperature' => ['value' => null, 'time' => null],
                    'Humidity' => ['value' => null, 'time' => null],
                ];

                $zones = $field->zones;
                foreach ($zones as $zone) {
                    $measures = $wiseconnService->getAllCurrentMeasures($field, $zone);
                    foreach (['Wind Velocity', 'Temperature', 'Humidity'] as $sensorType) {
                        if (isset($measures[$sensorType]['value']) && isset($measures[$sensorType]['time'])) {
                            $measureTime = Carbon::parse($measures[$sensorType]['time']);
                            if ($latestValues[$sensorType]['time'] === null || $measureTime->greaterThan(Carbon::parse($latestValues[$sensorType]['time']))) {
                                $latestValues[$sensorType] = [
                                    'value' => $measures[$sensorType]['value'],
                                    'time' => $measures[$sensorType]['time'],
                                ];
                            }
                        }
                    }
                }

                return $latestValues;
            });
        }

        Log::info('Caché de datos climáticos completada para todas las fields.');
    }
}