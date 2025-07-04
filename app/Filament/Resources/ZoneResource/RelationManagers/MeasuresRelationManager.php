<?php

namespace App\Filament\Resources\ZoneResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class MeasuresRelationManager extends RelationManager
{
    protected static string $relationship = 'measures';

    protected static ?string $title = 'Medidas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('sensor_type')
                    ->label('Tipo de Sensor'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state, $record) => $state !== null ? round($state, 2) . ' ' . $record->unit : 'N/D'),
                Tables\Columns\TextColumn::make('time')
                    ->label('Fecha/Hora')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'N/D'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sensor_type')
                    ->label('Tipo de Sensor')
                    ->options([
                        'Temperature' => 'Temperatura',
                        'Humidity' => 'Humedad',
                        'DailyRain' => 'Lluvia Diaria',
                        'MinTemperatureDaily' => 'Temp. Mín. Diaria',
                        'MaxTemperatureDaily' => 'Temp. Máx. Diaria',
                        'Chill Hours (Accumulated)' => 'Horas Frío (Acum.)',
                        'ChillHoursDaily' => 'Horas Frío (Diaria)',
                        'Wind Velocity' => 'Velocidad del Viento',
                        'Degree Days (Accumulated)' => 'Grados Día (Acum.)',
                        'Degree Days (Daily)' => 'Grados Día (Diaria)',
                        'Et0' => 'Evapotranspiración (Et0)',
                        'Etc' => 'Evapotranspiración (Etc)',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }
}