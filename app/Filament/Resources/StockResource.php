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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('field_id')
                    ->label('Campo')
                    ->relationship('field', 'name')
                    ->options(Field::all()->pluck('name', 'id'))
                    ->disabled()
                    ->required()
                    ->searchable(),

                Select::make('wharehouse_id')
                    ->label('Origen')
                    ->relationship('wharehouse', 'name')
                    ->options(Wharehouse::all()->pluck('name', 'id'))
                    ->required()
                    ->disabled()
                    ->searchable(),
                Select::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'product_name')
                    ->options(Product::all()->pluck('product_name', 'id'))
                    ->required()
                    ->disabled()
                    ->searchable(),
                forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->rules('required|numeric|min:1'),
                forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->rules('required|numeric|min:1'),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('field.name')
                    ->label('Campo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wharehouse.name')
                    ->label('Origen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn (User $user) => $user->can('edit field')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
                Tables\Actions\DeleteBulkAction::make()->visible(fn (User $user) => $user->can('delete field')),
            ]);
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
