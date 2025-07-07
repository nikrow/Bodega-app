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

    // Intervalo de actualización (polling)
    protected static ?string $pollingInterval = '15s';

    // Desactivar lazy loading para cargar inmediatamente
    protected static bool $isLazy = false;

    // Obtener el campo seleccionado desde el tenant
    protected function getSelectedField(): ?Field
    {
        $tenant = Filament::getTenant();
        return $tenant instanceof Field ? $tenant : null;
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

        // Clave del caché para el campo
        $cacheKey = "field_{$field->id}_latest_climate_values";
        $climateData = Cache::get($cacheKey);

        if (!$climateData) {
            return [
                Stat::make('Sin datos', 'N/A')
                    ->description('Datos climáticos no disponibles.')
                    ->color('warning'),
            ];
        }

        // Obtener datos diarios usando WiseconnService
        $wiseconnService = app(WiseconnService::class);
        $today = Carbon::now('America/Santiago');
        $initTime = $today->startOfDay()->toIso8601String();
        $endTime = $today->endOfDay()->toIso8601String();

        $minTemp = null;
        $maxTemp = null;
        $dailyRain = null;

        // Obtener la primera zona del campo para las medidas diarias
        $zone = $field->zones->first();

        if ($zone) {
            try {
                // Temperatura mínima diaria
                $minTemp = $wiseconnService->getDailyMinMaxMeasure($field, $zone, 'Temperature', $initTime, $endTime, 'min');
                // Temperatura máxima diaria
                $maxTemp = $wiseconnService->getDailyMinMaxMeasure($field, $zone, 'Temperature', $initTime, $endTime, 'max');
            
                // Lluvia acumulada diaria
                $dailyRain = $wiseconnService->getDailySumMeasure($field, $zone, 'Rain', $initTime, $endTime);
            } catch (\Exception $e) {
                Log::error("Error al obtener datos diarios para el campo {$field->id}: {$e->getMessage()}");
            }
        }

        // Preparar las estadísticas
        $stats = [];

        // Velocidad del viento
        $stats[] = Stat::make('Velocidad del Viento', $climateData['Wind Velocity']['value'] ? number_format($climateData['Wind Velocity']['value'], 1) . ' m/s' : 'N/A')
            ->description($climateData['Wind Velocity']['time'] ? Carbon::parse($climateData['Wind Velocity']['time'])->diffForHumans() : 'Sin datos')
            ->descriptionIcon('bi-wind')
            ->color($climateData['Wind Velocity']['value'] ? 'success' : 'gray');

        // Temperatura actual
        $stats[] = Stat::make('Temperatura', $climateData['Temperature']['value'] ? number_format($climateData['Temperature']['value'], 1) . ' °C' : 'N/A')
            ->description($climateData['Temperature']['time'] ? Carbon::parse($climateData['Temperature']['time'])->diffForHumans() : 'Sin datos')
            ->descriptionIcon('carbon-temperature-celsius')
            ->color($climateData['Temperature']['value'] ? 'success' : 'gray');

        // Humedad
        $stats[] = Stat::make('Humedad', $climateData['Humidity']['value'] ? number_format($climateData['Humidity']['value'], 1) . '%' : 'N/A')
            ->description($climateData['Humidity']['time'] ? Carbon::parse($climateData['Humidity']['time'])->diffForHumans() : 'Sin datos')
            ->descriptionIcon('carbon-humidity-alt')
            ->color($climateData['Humidity']['value'] ? 'success' : 'gray');

        // Temperatura mínima diaria
        $stats[] = Stat::make('Temp. Mínima Diaria', $minTemp ? number_format($minTemp, 1) . ' °C' : 'N/A')
            ->description($minTemp ? 'Hoy' : 'Sin datos')
            ->descriptionIcon('heroicon-m-arrow-down')
            ->color($minTemp ? 'info' : 'gray');
        $stats[] = Stat::make('Temp. Máxima Diaria', $maxTemp ? number_format($maxTemp, 1) . ' °C' : 'N/A')
            ->description($maxTemp ? 'Hoy' : 'Sin datos')
            ->descriptionIcon('heroicon-m-arrow-up')
            ->color($maxTemp ? 'info' : 'gray');
        // Lluvia acumulada diaria
        $stats[] = Stat::make('Lluvia Acumulada', $dailyRain ? number_format($dailyRain, 1) . ' mm' : 'N/A')
            ->description($dailyRain ? 'Hoy' : 'Sin datos')
            ->descriptionIcon('carbon-rain')
            ->color($dailyRain ? 'info' : 'gray');

        return $stats;
    }
}