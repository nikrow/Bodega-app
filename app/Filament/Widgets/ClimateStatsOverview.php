<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Zone;
use App\Models\Field;
use Filament\Facades\Filament;
use App\Services\WiseconnService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ClimateStatsOverview extends BaseWidget
{
    // Título dinámico del widget
    protected function getHeading(): ?string
    {
        $field = $this->getSelectedField();
        return $field ? 'Resumen Climático: ' . $field->name : 'Resumen';
    }

    // Descripción del widget
    protected function getDescription(): ?string
    {
        return 'Últimos valores climáticos.';
    }

    // Intervalo de actualización (polling) - aumentado para reducir carga
    protected static ?string $pollingInterval = '10m'; 

    // Activar lazy loading para no cargar hasta que sea visible
    protected static bool $isLazy = true;

    // Obtener el campo seleccionado desde el tenant, con eager loading
    protected function getSelectedField(): ?Field
    {
        $tenant = Filament::getTenant();
        if ($tenant instanceof Field) {
            return Field::with('zones')->find($tenant->id); // Eager load zones
        }
        return null;
    }

    protected function getStats(): array
    {
        $field = $this->getSelectedField();

        if (!$field) {
            return [
                Stat::make('Sin datos', 'N/A')
                    ->description('No se encontró un campo seleccionado.')
                    ->color('danger'),
            ];
        }

        // Clave del caché para datos latest (mantiene el original)
        $cacheKeyLatest = "field_{$field->id}_latest_climate_values";
        $climateData = Cache::get($cacheKeyLatest);

        if (!$climateData) {
            return [
                Stat::make('Sin datos', 'N/A')
                    ->description('Datos climáticos no disponibles.')
                    ->color('warning'),
            ];
        }

        // Preparar fechas para datos diarios
        $today = Carbon::now('America/Santiago');
        $initTime = $today->startOfDay()->toIso8601String();
        $endTime = $today->endOfDay()->toIso8601String();

        // Clave de caché para datos diarios (TTL hasta medianoche)
        $cacheKeyDaily = "field_{$field->id}_daily_climate_{$today->format('Y-m-d')}";
        $dailyData = Cache::remember($cacheKeyDaily, $today->endOfDay()->diffInSeconds(now()), function () use ($field, $initTime, $endTime) {
            $wiseconnService = app(WiseconnService::class);
            $minTemp = null;
            $maxTemp = null;
            $dailyRain = null;

            // Obtener la primera zona (ya eager-loaded)
            $zone = $field->zones->first();

            if ($zone) {
                try {
                    $minTemp = $wiseconnService->getDailyMinMaxMeasure($field, $zone, 'Temperature', $initTime, $endTime, 'min');
                    $maxTemp = $wiseconnService->getDailyMinMaxMeasure($field, $zone, 'Temperature', $initTime, $endTime, 'max');
                    $dailyRain = $wiseconnService->getDailySumMeasure($field, $zone, 'Rain', $initTime, $endTime);
                } catch (\Exception $e) {
                    Log::error("Error al obtener datos diarios para el campo {$field->id}: {$e->getMessage()}");
                }
            }

            return compact('minTemp', 'maxTemp', 'dailyRain');
        });

        // Preparar las estadísticas
        $stats = [];

        // Velocidad del viento
        $stats[] = Stat::make('Velocidad del Viento', $climateData['Wind Velocity']['value'] ?? 'N/A')
            ->value(fn() => $climateData['Wind Velocity']['value'] ? number_format($climateData['Wind Velocity']['value'], 1) . ' m/s' : 'N/A')
            ->description($climateData['Wind Velocity']['time'] ? Carbon::parse($climateData['Wind Velocity']['time'])->diffForHumans() : 'Sin datos')
            ->descriptionIcon('bi-wind')
            ->color($climateData['Wind Velocity']['value'] ? 'success' : 'gray');

        // Temperatura actual
        $stats[] = Stat::make('Temperatura', $climateData['Temperature']['value'] ?? 'N/A')
            ->value(fn() => $climateData['Temperature']['value'] ? number_format($climateData['Temperature']['value'], 1) . ' °C' : 'N/A')
            ->description($climateData['Temperature']['time'] ? Carbon::parse($climateData['Temperature']['time'])->diffForHumans() : 'Sin datos')
            ->descriptionIcon('carbon-temperature-celsius')
            ->color($climateData['Temperature']['value'] ? 'success' : 'gray');

        // Humedad
        $stats[] = Stat::make('Humedad', $climateData['Humidity']['value'] ?? 'N/A')
            ->value(fn() => $climateData['Humidity']['value'] ? number_format($climateData['Humidity']['value'], 1) . '%' : 'N/A')
            ->description($climateData['Humidity']['time'] ? Carbon::parse($climateData['Humidity']['time'])->diffForHumans() : 'Sin datos')
            ->descriptionIcon('carbon-humidity-alt')
            ->color($climateData['Humidity']['value'] ? 'success' : 'gray');

        // Datos diarios cacheados
        $stats[] = Stat::make('Temp. Mínima Diaria', $dailyData['minTemp'] ? number_format($dailyData['minTemp'], 1) . ' °C' : 'N/A')
            ->description($dailyData['minTemp'] ? 'Hoy' : 'Sin datos')
            ->descriptionIcon('heroicon-m-arrow-down')
            ->color($dailyData['minTemp'] ? 'info' : 'gray');

        $stats[] = Stat::make('Temp. Máxima Diaria', $dailyData['maxTemp'] ? number_format($dailyData['maxTemp'], 1) . ' °C' : 'N/A')
            ->description($dailyData['maxTemp'] ? 'Hoy' : 'Sin datos')
            ->descriptionIcon('heroicon-m-arrow-up')
            ->color($dailyData['maxTemp'] ? 'info' : 'gray');

        $stats[] = Stat::make('Lluvia Acumulada', $dailyData['dailyRain'] ? number_format($dailyData['dailyRain'], 1) . ' mm' : 'N/A')
            ->description($dailyData['dailyRain'] ? 'Hoy' : 'Sin datos')
            ->descriptionIcon('carbon-rain')
            ->color($dailyData['dailyRain'] ? 'info' : 'gray');

        return $stats;
    }
}