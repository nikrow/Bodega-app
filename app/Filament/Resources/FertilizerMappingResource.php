<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FertilizerMappingResource\Pages;
use App\Filament\Resources\FertilizerMappingResource\RelationManagers;
use App\Models\FertilizerMapping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FertilizerMappingResource extends Resource
{
    protected static ?string $model = FertilizerMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Anexos';
    protected static ?string $navigationLabel = 'Mapping Fertilizantes';
    protected static ?string $label = 'Mapping Fertilizante';
    protected static ?string $title = 'Mapping Fertilizante';
    protected static ?string $description = 'Mapping de fertilizantes para la carga de datos en ICC';
    protected static ?string $slug = 'fertilizer-mappings';
    protected static ?string $modelLabel = 'Mapping fertilizante';
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('fertilizer_name')
                        ->label('Nombre del Fertilizante')
                        ->required()
                        ->maxLength(255)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            
                            $normalized = strtolower($state);
                            $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
                            $normalized = trim($normalized, '_');
                            $set('excel_column_name', $normalized . '_l');
                        }),
                        Forms\Components\TextInput::make('excel_column_name')
                            ->label('Valor normalizado')
                            ->required()
                            ->unique(FertilizerMapping::class, 'excel_column_name', ignoreRecord: true)
                            ->readOnly()
                            ->dehydrated(true),
                        Forms\Components\Select::make('product_id')
                            ->label('Producto')
                            ->searchable()
                            ->relationship('product', 'product_name')
                            ->required(),
                        Forms\Components\TextInput::make('dilution_factor') 
                            ->label('Factor de Dilución')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
                        
                    ])
                    ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fertilizer_name')
                    ->label('Producto ICC')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('dilution_factor')
                    ->label('Factor de Dilución')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])->filters([
                //
            ])->headerActions([
            ])->actions([
                //
            ])->bulkActions([
                //
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
            'index' => Pages\ListFertilizerMappings::route('/'),
            'create' => Pages\CreateFertilizerMapping::route('/create'),
            'edit' => Pages\EditFertilizerMapping::route('/{record}/edit'),
        ];
    }
}
