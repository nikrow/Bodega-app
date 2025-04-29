<?php

namespace App\Filament\Resources\TractorResource\RelationManagers;

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
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('field.name')
                    ->label('Campo'),
                Tables\Columns\TextColumn::make('operator.name')
                    ->label('Operador'),
                Tables\Columns\TextColumn::make('machinery.name')
                    ->label('Equipo'),
                Tables\Columns\TextColumn::make('work.name')
                    ->label('Labor'),
                Tables\Columns\TextColumn::make('hours')
                    ->summarize(Sum::make())
                    ->label('Horas'),
                Tables\Columns\TextColumn::make('initial_hourometer')
                    ->label('Horómetro Inicial'),
                Tables\Columns\TextColumn::make('hourometer')
                    ->label('Horómetro Final'),
                Tables\Columns\IconColumn::make('approved')
                    ->label('Aprobado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('observations')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Observaciones'),
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
