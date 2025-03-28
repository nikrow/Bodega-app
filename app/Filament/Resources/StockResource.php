<?php

namespace App\Filament\Resources;


use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers\MovimientosRelationManager;
use App\Models\Stock;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'fas-boxes-stacked';
    protected static ?string $navigationLabel = 'Stock';
    protected static ?string $navigationGroup = 'Bodega';


    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->defaultSort('product.product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.SAP_code')
                    ->label('Código SAP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Bodega')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.family')
                    ->label('Familia')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad Disponible')
                    ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Valor')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id', 'Bodega')
                ->options(Warehouse::all()->pluck('name', 'id')),
            ])
            ->actions([

            ])
            ->bulkActions([
                    ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovimientosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
            'view' => Pages\ViewStock::route('/{record}'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
