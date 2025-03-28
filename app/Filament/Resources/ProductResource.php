<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Package;
use App\Models\Product;
use Filament\Forms\Form;
use App\Enums\FamilyType;
use Filament\Tables\Table;
use App\Enums\UnidadMedida;
use App\Enums\SapFamilyType;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Google\Service\Compute\Zone;
use App\Filament\Resources\ProductResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\PackagesRelationManager;

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
                    ->options(SapFamilyType::class)
                    ->required(),
                Forms\Components\Select::make('family')
                    ->label('Grupo')
                    ->options(FamilyType::class)
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->placeholder('Precio')
                    ->suffix('USD')
                    ->numeric(3),

                Forms\Components\Select::make('unit_measure')
                    ->label('Unidad de Medida')
                    ->options(UnidadMedida::class)
                    ->required(),
                Forms\Components\TextInput::make('sag_code')
                    ->label('Código SAG')
                    ->rules(function (Forms\Get $get) {
                        return Rule::unique('products', 'sag_code')
                            ->ignore($get('id'));
                    }),
                
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
                    ->suffix('dias')
                    ->required()
                    ->default(0)
                    ->numeric(),

                Forms\Components\TextInput::make('reentry')
                    ->label('Reingreso')
                    ->suffix('horas')
                    ->default(0)
                    ->required()
                    ->numeric()
                    ->reactive(),

                    /* Forms\Components\Select::make('packages')
                    ->label('Envases')
                    ->relationship('packages', 'name')
                    ->multiple()
                    ->required()
                    ->preload()
                    ->options(Package::all()->pluck('name', 'id'))
                    ->placeholder('Seleccione uno o más envases'),
                 */
                Forms\Components\Toggle::make('requires_batch_control')
                    ->label('Requiere Control de Lote')
                    ->onColor('success')
                    ->default(false),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('active_ingredients')
                    ->label('Ingredientes activos')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('family')
                    ->label('Grupo')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('SAP_code')
                    ->label('Código SAP')
                    ->copyable()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_measure')
                    ->label('Unidad de Medida')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sag_code')
                    ->label('Código SAG')
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('packages.name')
                    ->label('Envases')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio USD')
                    ->sortable()
                    ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('requires_batch_control')
                    ->label('Control de Lote')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                    Tables\Filters\SelectFilter::make('family')
                    ->label('Grupo')
                    ->options(FamilyType::class)
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
            AuditsRelationManager::class
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
