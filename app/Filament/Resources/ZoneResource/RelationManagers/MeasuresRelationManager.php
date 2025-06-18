<?php

namespace App\Filament\Resources\ZoneResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class MeasuresRelationManager extends RelationManager
{
    protected static string $relationship = 'measures';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('measure_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->required(),
                Forms\Components\DateTimePicker::make('time')
                    ->required(),
                Forms\Components\Select::make('sensor_type')
                    ->options([
                        'temperature' => 'Temperatura',
                        'rain' => 'Lluvia',
                        'humidity' => 'Humedad',
                        'windDirection' => 'Dirección Viento',
                        'unknown' => 'Desconocido',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('measure_id')->sortable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('value')->sortable(),
                Tables\Columns\TextColumn::make('time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('sensor_type'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sensor_type')
                    ->options([
                        'temperature' => 'Temperatura',
                        'rain' => 'Lluvia',
                        'humidity' => 'Humedad',
                        'windDirection' => 'Dirección Viento',
                        'unknown' => 'Desconocido',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}