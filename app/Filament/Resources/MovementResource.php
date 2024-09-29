<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovementResource\Pages;
use App\Filament\Resources\MovementResource\RelationManagers;
use App\Models\Field;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Wharehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovementResource extends Resource
{
    protected static ?string $model = Movement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('product_id')
                    ->label('Producto')

                    ->required()
                    ->rules('required'),
                Forms\Components\Select::make('field_id')
                    ->label('Campo')
                    ->options([Field::class, 'name', 'id'])
                    ->required()
                    ->searchable()
                    ->rules('required'),
                Forms\Components\Select::make('bodegaOrigen_id')
                    ->label('Origen')
                    ->options([Wharehouse::class, 'name', 'id'])
                    ->required()
                    ->searchable()
                    ->visible(fn ($get) => $get('tipo') !== 'entrada')
                    ->rules('required'),
                Forms\Components\Select::make('bodegaDestino_id')
                    ->label('Destino')
                    ->options([Wharehouse::class, 'name', 'id'])
                    ->required()
                    ->searchable()
                    ->rules('required'),
                Forms\Components\TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->required()
                    ->rules('required'),
                Forms\Components\select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'traslado' => 'Traslado',
                    ])
                    ->default('entrada')
                    ->required()
                    ->rules('required'),
                Forms\Components\TextInput::make('descripcion')
                    ->label('Descripcion')
                    ->required()
                    ->rules('required'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_id')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('field_id')
                    ->label('Campo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_origen_id')
                    ->label('Origen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_destino_id')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripcion')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modificado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y H:i')
                    ->sortable(),
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
            'index' => Pages\ListMovements::route('/'),
            'create' => Pages\CreateMovement::route('/create'),
            'edit' => Pages\EditMovement::route('/{record}/edit'),
        ];
    }
}
