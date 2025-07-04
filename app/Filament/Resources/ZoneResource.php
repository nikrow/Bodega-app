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
use App\Filament\Resources\ZoneResource\RelationManagers;
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

    public static function table(Table $table): Table
    {
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
                TextColumn::make('summary.current_temperature')
                    ->label('Temp. Actual (°C)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->current_temperature !== null ? round($summary->current_temperature, 2) . '°' : 'N/D';
                    })
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->current_temperature_time ? Carbon::parse($summary->current_temperature_time)->format('d/m/Y H:i') : null;
                    }),

                // Min Temperature Daily
                TextColumn::make('summary.min_temperature_daily')
                    ->label('Temp. Mín. Hoy (°C)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->min_temperature_daily !== null ? round($summary->min_temperature_daily, 2) . '°' : 'N/D';
                    })
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->min_temperature_time ? Carbon::parse($summary->min_temperature_time)->format('d/m/Y H:i') : null;
                    })
                    ->color('secondary'),

                // Max Temperature Daily
                TextColumn::make('summary.max_temperature_daily')
                    ->label('Temp. Máx. Hoy (°C)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->max_temperature_daily !== null ? round($summary->max_temperature_daily, 2) . '°' : 'N/D';
                    })
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->max_temperature_time ? Carbon::parse($summary->max_temperature_time)->format('d/m/Y H:i') : null;
                    })
                    ->color('warning'),

                // Daily Rain
                TextColumn::make('summary.daily_rain')
                    ->label('Lluvia Hoy (mm)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->daily_rain !== null ? round($summary->daily_rain, 2) . 'mm' : 'N/D';
                    })
                    ->color('primary'),

                // Current Humidity
                TextColumn::make('summary.current_humidity')
                    ->label('Humedad Actual (%)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->current_humidity !== null ? round($summary->current_humidity, 2) . '%' : 'N/D';
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
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->current_humidity_time ? Carbon::parse($summary->current_humidity_time)->format('d/m/Y H:i') : null;
                    }),

                // Chill Hours Accumulated
                TextColumn::make('summary.chill_hours_accumulated')
                    ->label('Horas Frío (Acum.)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->chill_hours_accumulated !== null ? round($summary->chill_hours_accumulated, 2) : 'N/D';
                    })
                    ->color('secondary')
                    ->tooltip(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->chill_hours_accumulated_time ? Carbon::parse($summary->chill_hours_accumulated_time)->format('d/m/Y H:i') : null;
                    }),

                // Chill Hours Daily
                TextColumn::make('summary.chill_hours_daily')
                    ->label('Horas Frío (Hoy)')
                    ->state(function (Zone $record): ?string {
                        $summary = $record->summary;
                        return $summary && $summary->chill_hours_daily !== null ? round($summary->chill_hours_daily, 2) : 'N/D';
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
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MeasuresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with('summary');
    }
}