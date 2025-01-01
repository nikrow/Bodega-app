<?php
namespace App\Filament\Resources\StockResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('producto.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Bodega')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Cantidad')
                    ->numeric(),
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Orden'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Opcional: Agregar filtros para fechas, tipo de movimiento, etc.
            ])
            ->actions([
                // Opcional: Acciones individuales por registro
            ])
            ->bulkActions([
                // Opcional: Acciones en lote
            ]);
    }


}
