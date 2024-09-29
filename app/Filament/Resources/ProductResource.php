<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'bi-box-seam';

    protected static ?string $navigationGroup = 'Anexos';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'Producto';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->label('Nombre')
                    ->placeholder('Nombre del producto')
                    ->rules('required', 'max:255'),
                Forms\Components\TextInput::make('active_ingredients')
                    ->placeholder('Ingredientes activos')
                    ->label('Ingredientes activos')
                    ->nullable(),
                Forms\Components\TextInput::make('SAP_code')
                    ->label('Código SAP')
                    ->rules('required'),
                Forms\Components\Select::make('family')
                    ->label('Familia SAP')
                    ->options([
                        'Fert_Fertilizantes' => 'Fert_Fertilizantes',
                        'Fert_Miscelaneos' => 'Fert_Miscelaneos',
                        'Quim_Herbicida' => 'Quim_Herbicida',
                        'Quim_Pesticida' => 'Quim_Pesticida',
                    ])
                    ->rules('required'),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->placeholder('Precio')
                    ->rules('required'),
                Forms\Components\Select::make('category_id')
                    ->label('Categoría')
                    ->options(Category::all()->pluck('name', 'id')->toArray())
                    ->rules('required'),

                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('active_ingredients')
                    ->sortable()
                    ->label('Ingredientes activos'),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label('Categoría'),
                Tables\Columns\TextColumn::make('SAP_code')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Código SAP'),
                Tables\Columns\TextColumn::make('family')
                    ->sortable()
                    ->label('Familia SAP'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Precio'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->date('d/m/Y H:i')
                    ->label('Modificado el'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
