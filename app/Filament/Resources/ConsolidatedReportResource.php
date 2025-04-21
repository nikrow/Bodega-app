<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ConsolidatedReport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ConsolidatedReportResource\Pages;

class ConsolidatedReportResource extends Resource
{
    protected static ?string $model = ConsolidatedReport::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Maquinaria';
    protected static ?string $navigationLabel = 'Reports consolidados';
    protected static ?string $slug = 'reports-consolidados';
    protected static ?string $modelLabel = 'Reporte consolidado';
    protected static ?string $pluralModelLabel = 'Reportes consolidados';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
    return parent::getEloquentQuery()->with(['report.work']);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('report.date')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('report.field.name')
                    ->label('Campo'), 
                    
                Tables\Columns\TextColumn::make('report.operator.name')
                    ->label('Operador'),

                Tables\Columns\TextColumn::make('equipment')
                    ->label('Equipo')
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery->name 
                            : $record->tractor->name;
                    }),
                Tables\Columns\TextColumn::make('report.initial_hourometer')
                    ->label('Horómetro inicial')
                    ->numeric(),
                    
                Tables\Columns\TextColumn::make('report.hourometer')
                    ->label('Horómetro final')
                    ->numeric(),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas')
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery_hours 
                            : $record->tractor_hours;
                    }),

                Tables\Columns\TextColumn::make('report.work.name')
                    ->label('Labores'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery->price 
                            : $record->tractor->price;
                    }),

                Tables\Columns\TextColumn::make('report.work.cost_type')
                    ->label('Centro de Costo'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(function ($record) {
                        return $record->machinery_id 
                            ? $record->machinery_total 
                            : $record->tractor_total;
                    }),

                Tables\Columns\TextColumn::make('report.approvedBy.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Aprovado por'),    
                
                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Generado'),
            ])
            ->filters([
                // Puedes agregar filtros aquí si es necesario
            ])
            ->actions([
                
            ])
            ->bulkActions([
                ExportBulkAction::make('export')
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsolidatedReports::route('/'),
        ];
    }
}