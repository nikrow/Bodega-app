<?php

namespace App\Filament\Resources\OperatorAssignmentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                Tables\Columns\TextColumn::make('field.name')
                    ->label('Campo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tractor.name')
                    ->label('Máquina')
                    ->sortable(),
                Tables\Columns\TextColumn::make('machinery.name')
                    ->label('Equipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('work.name')
                    ->label('Labor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas')
                    ->numeric(decimalPlaces: 2)
                    ->summarize(Sum::make()->label('Total Horas'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('initial_hourometer')
                    ->label('Horómetro Inicial')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('hourometer')
                    ->label('Horómetro Final')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\IconColumn::make('approved')
                    ->label('Aprobado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('observations')
                    ->label('Observaciones')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('current_month')
                ->query(function (Builder $query) {
                    $query->whereBetween('date', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]);
                })
                ->default()
                ->toggle()
                ->label('Mes Actual'),
            
        ], layout: FiltersLayout::AboveContent)
        ->filtersFormColumns(3)
        ->headerActions([
            
        ])
        ->actions([
            
        ])
        ->bulkActions([
                ExportBulkAction::make('export')
        ]);
}
}