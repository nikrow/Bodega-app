<?php

namespace App\Filament\Resources;

use App\Enums\FamilyType;
use App\Enums\SapFamilyType;
use App\Enums\UnidadMedida;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Field;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

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
                    ->unique()
                    ->placeholder('Nombre del producto')
                    ->rules('required', 'max:255'),
                Forms\Components\TextInput::make('active_ingredients')
                    ->placeholder('Ingredientes activos')
                    ->label('Ingredientes activos')
                    ->nullable(),
                Forms\Components\TextInput::make('SAP_code')
                    ->label('Código SAP')
                    ->unique()
                    ->rules('required', 'max:255'),

                Forms\Components\Select::make('SAP_family')
                    ->label('Familia SAP')
                    ->options([
                        SapFamilyType::FERTILIZANTES->value => 'fertilizantes-enmiendas',
                        SapFamilyType::FITOSANITARIOS->value => 'fitosanitarios',
                        SapFamilyType::FITOREGULADORES->value => 'fitoreguladores',
                        SapFamilyType::BIOESTIMULANTES->value => 'bioestimulantes',
                        SapFamilyType::OTROS->value => 'otros',
                    ])
                    ->rules('required'),
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
                    ->rules('required'),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->placeholder('Precio')
                    ->suffix('USD')
                    ->rules('required','money'),

                Forms\Components\Select::make('unit_measure')
                    ->label('Unidad de Medida')
                    ->options([
                        UnidadMedida::KILOGRAMO->value => 'kilogramo',
                        UnidadMedida::LITRO->value => 'litro',
                        UnidadMedida::UNIDAD->value => 'unidad',
                    ])
                    ->rules('required'),
                Forms\Components\TextInput::make('dosis_min')
                    ->label('Dosis Min')
                    ->helperText ('Lt-Kg/100l')
                    ->rules('required', 'decimal'),
                Forms\Components\TextInput::make('dosis_max')
                    ->label('Dosis Max')
                    ->helperText ('Lt-Kg/100l')
                    ->rules('required', 'decimal'),
                Forms\Components\TextInput::make('waiting_time')
                    ->label('Carencia'),

                Forms\Components\TextInput::make('reentry')
                    ->label('Reingreso')
                    ->reactive(),


                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('family')
            ->groups([
                Group::make('family')
                    ->collapsible()
                    ->label('Grupo'),
            ])
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
            AuditsRelationManager::class,
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
