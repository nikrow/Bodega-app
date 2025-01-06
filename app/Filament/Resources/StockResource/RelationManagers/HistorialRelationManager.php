<?php

namespace App\Filament\Resources\StockResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HistorialRelationManager extends RelationManager
{
    protected static string $relationship = 'historial';


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d-m-Y H:i:s'),
                TextColumn::make('movement_type')
                    ->label('Tipo'),
                TextColumn::make('quantity_snapshot')
                    ->numeric(2)
                    ->label('Cantidad Stock'),
                TextColumn::make('movement_id')
                    ->label('ID movimiento'),
                TextColumn::make('price_snapshot')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Precio'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
