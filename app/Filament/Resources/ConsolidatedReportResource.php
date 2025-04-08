<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\ConsolidatedReport;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Resources\ConsolidatedReportResource\Pages;

class ConsolidatedReportResource extends Resource
{
    protected static ?string $model = ConsolidatedReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Maquinaria';
    protected static ?string $navigationLabel = 'Consolidated Reports';
    protected static ?string $slug = 'consolidated-reports';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_start')
                    ->date('d/m/Y')
                    ->label('Inicio'),
                Tables\Columns\TextColumn::make('period_end')
                    ->date('d/m/Y')
                    ->label('Fin'),
                Tables\Columns\TextColumn::make('tractor.name')
                    ->label('Máquina'),
                Tables\Columns\TextColumn::make('machinery.name')
                    ->label('Equipo'),
                Tables\Columns\TextColumn::make('work.name')
                    ->label('Labor'),
                Tables\Columns\TextColumn::make('work.costCenter.name')
                    ->label('Centro de Costo'),
                Tables\Columns\TextColumn::make('tractor_hours')
                    ->label('Horas Tractor'),
                Tables\Columns\TextColumn::make('tractor_total')
                    ->label('Total Tractor'),
                Tables\Columns\TextColumn::make('machinery_hours')
                    ->label('Horas Equipo'),
                Tables\Columns\TextColumn::make('machinery_total')
                    ->label('Total Equipo'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por'),
                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime()
                        ->label('Generado'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsolidatedReports::route('/'),
        ];
    }
}