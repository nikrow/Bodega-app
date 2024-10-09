<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers;
use App\Models\Field;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Wharehouse;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationGroup = 'Bodega';



    protected static ?int $navigationSort = 10;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wharehouse.name')
                    ->label('Bodega')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad Disponible')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wharehouse_id')
                    ->label('Bodega')
                    ->options(Wharehouse::all()->pluck('name', 'id')),
            ])
            ->actions([]) // Sin acciones de edición o eliminación
            ->bulkActions([]); // Sin acciones de selección múltiple
    }


    public static function getRelations(): array
    {
        return [

            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),

            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
