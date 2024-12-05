<?php

namespace App\Filament\Resources;

use App\Enums\FamilyType;
use App\Enums\SapFamilyType;
use App\Enums\UnidadMedida;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationIcon = 'bi-box-seam';
    protected static ?string $tenantOwnershipRelationshipName = 'field';
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
                    ->required()
                    ->placeholder('Nombre del producto')
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('products', 'product_name')
                            ->ignore($get('id'));
                    }),
                Forms\Components\TextInput::make('active_ingredients')
                    ->placeholder('Ingredientes activos')
                    ->label('Ingredientes activos')
                    ->nullable(),
                Forms\Components\TextInput::make('SAP_code')
                    ->label('Código SAP')
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('products', 'SAP_code')
                            ->ignore($get('id'));
                    }),

                Forms\Components\Select::make('SAP_family')
                    ->label('Familia SAP')
                    ->options([
                        SapFamilyType::FERTILIZANTES->value => 'fertilizantes-enmiendas',
                        SapFamilyType::FITOSANITARIOS->value => 'fitosanitarios',
                        SapFamilyType::FITOREGULADORES->value => 'fitoreguladores',
                        SapFamilyType::BIOESTIMULANTES->value => 'bioestimulantes',
                        SapFamilyType::OTROS->value => 'otros',
                    ])
                    ->required(),
                Forms\Components\Select::make('family')
                    ->label('Grupo')
                    ->options([
                        FamilyType::ACARICIDA->value => 'acaricida',
                        FamilyType::BLOQUEADOR->value => 'bloqueador',
                        FamilyType::BIOESTIMULANTE->value => 'bioestimulante',
                        FamilyType::HERBICIDA->value => 'herbicida',
                        FamilyType::INSECTICIDA->value => 'insecticida',
                        FamilyType::FERTILIZANTE->value => 'fertilizante',
                        FamilyType::FUNGICIDA->value => 'fungicida',
                        FamilyType::REGULADOR->value => 'regulador',

                    ])
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->placeholder('Precio')
                    ->suffix('USD')
                    ->numeric(3),

                Forms\Components\Select::make('unit_measure')
                    ->label('Unidad de Medida')
                    ->options([
                        UnidadMedida::KILOGRAMO->value => 'kilogramo',
                        UnidadMedida::LITRO->value => 'litro',
                        UnidadMedida::UNIDAD->value => 'unidad',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('dosis_min')
                    ->label('Dosis Min')
                    ->helperText ('Lt-Kg/100l')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('dosis_max')
                    ->label('Dosis Max')
                    ->helperText ('Lt-Kg/100l')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('waiting_time')
                    ->label('Carencia')
                    ->numeric(),

                Forms\Components\TextInput::make('reentry')
                    ->label('Reingreso')
                    ->numeric()
                    ->reactive(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('active_ingredients')
                    ->label('Ingredientes activos')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('family')
                    ->label('Grupo')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('SAP_code')
                    ->label('Código SAP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_measure')
                    ->label('Unidad de Medida')
                    ->sortable(),
                Tables\Columns\TextColumn::make('SAP_family')
                    ->label('Familia SAP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                    Tables\Filters\SelectFilter::make('family')
                    ->label('Grupo')
                    ->options([
                        FamilyType::INSECTICIDA->value => 'insecticida',
                        FamilyType::HERBICIDA->value => 'herbicida',
                        FamilyType::FERTILIZANTE->value => 'fertilizante',
                        FamilyType::ACARICIDA->value => 'acaricida',
                        FamilyType::FUNGICIDA->value => 'fungicida',
                        FamilyType::BIOESTIMULANTE->value => 'bioestimulante',
                        FamilyType::REGULADOR->value => 'regulador',
                        FamilyType::BLOQUEADOR->value => 'bloqueador',
                        ])
                ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                    ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [

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
