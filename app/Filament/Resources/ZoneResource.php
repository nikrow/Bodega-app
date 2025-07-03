<?php

namespace App\Filament\Resources;

use App\Enums\RoleType;
use Carbon\Carbon;
use Filament\Forms;
use App\Models\Zone;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Services\WiseconnService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ZoneResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static ?string $navigationGroup = 'Aplicaciones';

    protected static ?string $navigationLabel = 'Estaciones';

    protected static ?string $modelLabel = 'Estacion';
    
    protected static ?string $pluralModelLabel = 'Estaciones';

    protected static ?int $navigationSort = 1;

    private static function getCachedMeasure($cacheKey, callable $fetchFunction, $ttl = 600)
    {
        return Cache::remember($cacheKey, $ttl, $fetchFunction);
    }

    public static function table(Table $table): Table
    {
        $wiseconnService = new WiseconnService();

        return $table
            ->columns([
                TextColumn::make('wiseconn_zone_id')
                    ->label('ID Wiseconn')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                // Current Temperature
                TextColumn::make('current_temperature')
                    ->label('Temp. Actual (°C)')
                    ->state(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return 'N/A';

                        $currentMeasures = $wiseconnService->getAllCurrentMeasures($field, $record);
                        $temperature = $currentMeasures['Temperature']['value'] ?? null;

                        return $temperature !== null ? round($temperature, 2) . '°' : 'N/D';
                    })
                    ->tooltip(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return null;

                        $currentMeasures = $wiseconnService->getAllCurrentMeasures($field, $record);
                        $lastDataDate = $currentMeasures['Temperature']['time'] ?? null;

                        return $lastDataDate ? Carbon::parse($lastDataDate)->format('d/m/Y H:i') : null;
                    }),

                // Min Temperature Daily
                    TextColumn::make('min_temperature_daily')
                ->label('Temp. Mín. Hoy (°C)')
                ->state(function (Zone $record) use ($wiseconnService): ?string {
                    $field = $record->field;
                    if (!$field) return 'N/A';

                    $cacheKey = "zone_{$record->id}_min_temperature_daily";
                    $data = self::getCachedMeasure($cacheKey, function () use ($wiseconnService, $field, $record) {
                        try {
                            $today = Carbon::now('America/Santiago');
                            $initTime = $today->startOfDay()->toIso8601String();
                            $endTime = $today->endOfDay()->toIso8601String();
                            
                            // Obtener medidas de temperatura en el rango de tiempo
                            $measures = $wiseconnService->getZoneMeasures($field, $record, $initTime, $endTime);
                            $temperatureData = $measures['Temperature'][0]['data'] ?? [];

                            if (empty($temperatureData)) {
                                return ['value' => null, 'time' => null];
                            }

                            // Encontrar el valor mínimo y su timestamp
                            $values = array_column($temperatureData, 'value');
                            $minValue = min($values);
                            $minIndex = array_search($minValue, $values);
                            $minTime = $temperatureData[$minIndex]['time'] ?? null;

                            return [
                                'value' => $minValue,
                                'time' => $minTime
                            ];
                        } catch (\Exception $e) {
                            Log::error("Error al obtener temp min para zona {$record->id}: " . $e->getMessage());
                            return ['value' => null, 'time' => null];
                        }
                    });

                    if (is_float($data) || is_int($data)) {
                        Cache::forget($cacheKey);
                        return 'N/D';
                    }

                    return $data['value'] !== null ? round($data['value'], 2) . '°' : 'N/D';
                })
                ->tooltip(function (Zone $record) use ($wiseconnService): ?string {
                    $field = $record->field;
                    if (!$field) return null;

                    $cacheKey = "zone_{$record->id}_min_temperature_daily";
                    $data = Cache::get($cacheKey);

                    if (is_float($data) || is_int($data)) {
                        Cache::forget($cacheKey);
                        return null;
                    }

                    // Formatear el timestamp en UTC sin cambiar la zona horaria
                    return $data && $data['time'] ? Carbon::parse($data['time'], 'UTC')->format('d/m/Y H:i') : null;
                })
                ->color('secondary'),

            // Max Temperature Daily
            TextColumn::make('max_temperature_daily')
                ->label('Temp. Máx. Hoy (°C)')
                ->state(function (Zone $record) use ($wiseconnService): ?string {
                    $field = $record->field;
                    if (!$field) return 'N/A';

                    $cacheKey = "zone_{$record->id}_max_temperature_daily";
                    $data = self::getCachedMeasure($cacheKey, function () use ($wiseconnService, $field, $record) {
                        try {
                            $today = Carbon::now('America/Santiago');
                            $initTime = $today->startOfDay()->toIso8601String();
                            $endTime = $today->endOfDay()->toIso8601String();
                            
                            // Obtener medidas de temperatura en el rango de tiempo
                            $measures = $wiseconnService->getZoneMeasures($field, $record, $initTime, $endTime);
                            $temperatureData = $measures['Temperature'][0]['data'] ?? [];

                            if (empty($temperatureData)) {
                                return ['value' => null, 'time' => null];
                            }

                            // Encontrar el valor máximo y su timestamp
                            $values = array_column($temperatureData, 'value');
                            $maxValue = max($values);
                            $maxIndex = array_search($maxValue, $values);
                            $maxTime = $temperatureData[$maxIndex]['time'] ?? null;

                            return [
                                'value' => $maxValue,
                                'time' => $maxTime
                            ];
                        } catch (\Exception $e) {
                            Log::error("Error al obtener temp max para zona {$record->id}: " . $e->getMessage());
                            return ['value' => null, 'time' => null];
                        }
                    });

                    if (is_float($data) || is_int($data)) {
                        Cache::forget($cacheKey);
                        return 'N/D';
                    }

                    return $data['value'] !== null ? round($data['value'], 2) . '°' : 'N/D';
                })
                ->tooltip(function (Zone $record) use ($wiseconnService): ?string {
                    $field = $record->field;
                    if (!$field) return null;

                    $cacheKey = "zone_{$record->id}_max_temperature_daily";
                    $data = Cache::get($cacheKey);

                    if (is_float($data) || is_int($data)) {
                        Cache::forget($cacheKey);
                        return null;
                    }

                    // Formatear el timestamp en UTC sin cambiar la zona horaria
                    return $data && $data['time'] ? Carbon::parse($data['time'], 'UTC')->format('d/m/Y H:i') : null;
                })
                ->color('warning'),
                // Daily Rain
                TextColumn::make('daily_rain')
                    ->label('Lluvia Hoy (mm)')
                    ->state(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return 'N/A';

                        $cacheKey = "zone_{$record->id}_daily_rain";
                        $data = self::getCachedMeasure($cacheKey, function () use ($wiseconnService, $field, $record) {
                            try {
                                $today = Carbon::now('America/Santiago');
                                $initTime = $today->startOfDay()->toIso8601String();
                                $endTime = $today->endOfDay()->toIso8601String();
                                return $wiseconnService->getDailySumMeasure($field, $record, 'Rain', $initTime, $endTime);
                            } catch (\Exception $e) {
                                Log::error("Error al obtener lluvia hoy para zona {$record->id}: " . $e->getMessage());
                                return null;
                            }
                        });

                        return $data !== null ? round($data, 2) . 'mm' : 'N/D';
                    })
                    ->color('primary'),

                // Current Humidity
                TextColumn::make('current_humidity')
                    ->label('Humedad Actual (%)')
                    ->state(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return 'N/A';

                        $currentMeasures = $wiseconnService->getAllCurrentMeasures($field, $record);
                        $humidity = $currentMeasures['Humidity']['value'] ?? null;

                        return $humidity !== null ? round($humidity, 2) . '%' : 'N/D';
                    })
                    ->color(function (?string $state): ?string {
                        if (str_contains($state, 'N/D') || str_contains($state, 'N/A')) {
                            return 'gray';
                        }
                        $value = (float) $state;
                        if ($value < 40) return 'warning';
                        if ($value > 80) return 'info';
                        return 'success';
                    })
                    ->tooltip(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return null;

                        $currentMeasures = $wiseconnService->getAllCurrentMeasures($field, $record);
                        $lastDataDate = $currentMeasures['Humidity']['time'] ?? null;

                        return $lastDataDate ? Carbon::parse($lastDataDate)->format('d/m/Y H:i') : null;
                    }),

                // Chill Hours Accumulated
                TextColumn::make('chill_hours_accumulated')
                    ->label('Horas Frío (Acum.)')
                    ->state(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return 'N/A';

                        $currentMeasures = $wiseconnService->getAllCurrentMeasures($field, $record);
                        $chillHours = $currentMeasures['Chill Hours (Accumulated)']['value'] ?? null;

                        return $chillHours !== null ? round($chillHours, 2) : 'N/D';
                    })
                    ->color('secondary')
                    ->tooltip(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return null;

                        $currentMeasures = $wiseconnService->getAllCurrentMeasures($field, $record);
                        $lastDataDate = $currentMeasures['Chill Hours (Accumulated)']['time'] ?? null;

                        return $lastDataDate ? Carbon::parse($lastDataDate)->format('d/m/Y H:i') : null;
                    }),

                // Chill Hours Daily
                TextColumn::make('chill_hours_daily')
                    ->label('Horas Frío (Hoy)')
                    ->state(function (Zone $record) use ($wiseconnService): ?string {
                        $field = $record->field;
                        if (!$field) return 'N/A';

                        $cacheKey = "zone_{$record->id}_chill_hours_daily";
                        $data = self::getCachedMeasure($cacheKey, function () use ($wiseconnService, $field, $record) {
                            try {
                                $today = Carbon::now('America/Santiago');
                                $initTime = $today->startOfDay()->toIso8601String();
                                $endTime = $today->endOfDay()->toIso8601String();
                                return $wiseconnService->getDailySumMeasure($field, $record, 'Chill Hours (Daily)', $initTime, $endTime);
                            } catch (\Exception $e) {
                                Log::error("Error al obtener horas frío diarias para zona {$record->id}: " . $e->getMessage());
                                return null;
                            }
                        });

                        return $data !== null ? round($data, 2) : 'N/D';
                    })
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_historical_initialized')
                    ->label('Datos Históricos Inicializados')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No'),
            ])
            ->actions([
                Tables\Actions\Action::make('initialize_historical')
                    ->label('Inicializar Histórico')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => Auth::user()->role === RoleType::ADMIN)
                    ->action(function (Zone $record) {
                        if ($record->is_historical_initialized) {
                            \Filament\Notifications\Notification::make()
                                ->title('Los datos históricos ya están inicializados.')
                                ->info()
                                ->send();
                            return;
                        }

                        $field = $record->field;
                        if ($field) {
                            (new \App\Services\WiseconnService())->initializeHistoricalMeasures($field, $record);
                            \Filament\Notifications\Notification::make()
                                ->title('Inicialización de datos históricos iniciada.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error: Campo asociado no encontrado.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}