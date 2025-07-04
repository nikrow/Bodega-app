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

    protected static ?string $navigationIcon = 'fluentui-weather-rain-showers-day-48-o';

    protected static ?string $navigationGroup = 'Aplicaciones';

    protected static ?string $navigationLabel = 'Estaciones';

    protected static ?string $modelLabel = 'Estacion';
    
    protected static ?string $pluralModelLabel = 'Estaciones';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wiseconn_zone_id')
                    ->label('ID Wiseconn')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                // Current Temperature
                TextColumn::make('summary.current_temperature')
                    ->label('Temp. Actual (°C)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->current_temperature)) return 'N/D';
                        return round($summary->current_temperature, 2) . '°';
                    })
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->current_temperature_time)) return null;
                        $date = $summary->current_temperature_time;
                        return $date->setTimezone('America/Santiago')->format('d/m/Y H:i');
                    }),

                // Min Temperature Daily
                TextColumn::make('summary.min_temperature_daily')
                    ->label('Temp. Mín. Hoy (°C)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->min_temperature_daily)) return 'N/D';
                        return round($summary->min_temperature_daily, 2) . '°';
                    })
                    ->color('secondary'),

                // Max Temperature Daily
                TextColumn::make('summary.max_temperature_daily')
                    ->label('Temp. Máx. Hoy (°C)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->max_temperature_daily)) return 'N/D';
                        return round($summary->max_temperature_daily, 2) . '°';
                    })
                    ->color('warning'),

                // Daily Rain
                TextColumn::make('summary.daily_rain')
                    ->label('Lluvia Hoy (mm)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->daily_rain)) return 'N/D';
                        return round($summary->daily_rain, 2) . 'mm';
                    })
                    ->color('primary'),

                // Current Humidity
                TextColumn::make('summary.current_humidity')
                    ->label('Humedad Actual (%)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->current_humidity)) return 'N/D';
                        return round($summary->current_humidity, 2) . '%';
                    })
                    ->color(function (?string $state): ?string {
                        if (str_contains($state, 'N/D') || str_contains($state, 'N/A')) {
                            return 'gray';
                        }
                        $value = (float) str_replace('%', '', $state);
                        if ($value < 40) return 'warning';
                        if ($value > 80) return 'info';
                        return 'success';
                    })
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->current_humidity_time)) return null;
                        $date = Carbon::parse($summary->current_humidity_time); // Parse como UTC
                        return $date->setTimezone('America/Santiago')->format('d/m/Y H:i');
                    }),

                // Chill Hours Accumulated
                TextColumn::make('summary.chill_hours_accumulated')
                    ->label('Horas Frío (Acum.)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->chill_hours_accumulated)) return 'N/D';
                        return round($summary->chill_hours_accumulated, 2);
                    })
                    ->color('secondary')
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->chill_hours_accumulated_time)) return null;
                        $date = Carbon::parse($summary->chill_hours_accumulated_time); // Parse como UTC
                        return $date->setTimezone('America/Santiago')->format('d/m/Y H:i');
                    }),

                // Chill Hours Daily
                TextColumn::make('summary.chill_hours_daily')
                    ->label('Horas Frío (Hoy)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->chill_hours_daily)) return 'N/D';
                        return round($summary->chill_hours_daily, 2);
                    })
                    ->color('info')
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        if (!$summary || is_null($summary->chill_hours_daily_time)) return null;
                        $date = Carbon::parse($summary->chill_hours_daily_time); // Parse como UTC
                        return $date->setTimezone('America/Santiago')->format('d/m/Y H:i');
                    }),
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
                // Puedes agregar ExportBulkAction si lo necesitas
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
            ])
            ->with('summary'); 
    }
}