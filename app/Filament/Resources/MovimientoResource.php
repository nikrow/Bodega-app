<?php

namespace App\Filament\Resources;

use App\Enums\MovementType;
use App\Filament\Resources\MovimientoResource\Pages;
use App\Filament\Resources\MovimientoResource\RelationManagers;
use App\Filament\Resources\MovimientoResource\RelationManagers\MovimientoProductosRelationManager;
use App\Models\Field;
use App\Models\Movimiento;
use App\Models\Wharehouse;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MovimientoResource extends Resource
{
    protected static ?string $model = Movimiento::class;

    protected static ?string $navigationGroup = 'Bodega';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->options([
                        MovementType::ENTRADA->value => 'entrada',
                        MovementType::SALIDA->value => 'salida',
                        MovementType::TRASLADO->value => 'traslado',
                    ])
                    ->required()
                    ->reactive(), // Muestra/oculta campos basados en el tipo de movimiento

                Select::make('field_id')
                    ->label('Campo')
                    ->relationship('field', 'name')
                    ->options(Field::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Select::make('bodega_origen_id')
                    ->label('Origen')
                    ->relationship('bodega_origen', 'name')
                    ->options(Wharehouse::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->visible(fn ($get) => $get('tipo') !== MovementType::ENTRADA->value),

                Select::make('bodega_destino_id')
                    ->label('Destino')
                    ->relationship('bodega_destino', 'name')
                    ->options(Wharehouse::all()->pluck('name', 'id'))
                    ->required()
                    ->visible(fn ($get) => $get('tipo') !== MovementType::SALIDA->value)
                    ->searchable(),
                forms\Components\TextInput::make('comprobante')
                    ->label('Comprobante')
                    ->rules('required|numeric|min:1'),
                forms\Components\TextInput::make('encargado')
                    ->label('Encargado')
                    ->rules('required'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('tipo')
            ->columns([

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            MovementType::ENTRADA => 'entrada',
                            MovementType::SALIDA => 'salida',
                            MovementType::TRASLADO => 'traslado',
                            default => 'Desconocido',

                        };
                    })
                    ->colors([
                        'entrada' => 'success',
                        'salida' => 'danger',
                        'traslado' => 'warning',

                    ]),
                Tables\Columns\TextColumn::make('movement_number')
                    ->label('ID Movimiento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('field.name')
                    ->label('Campo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_origen.name')
                    ->label('Origen')
                    ->Placeholder('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega_destino.name')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('comprobante')
                    ->label('Comprobante')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('encargado')
                    ->label('Encargado')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovimientoProductosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovimientos::route('/'),
            'create' => Pages\CreateMovimiento::route('/create'),
            'edit' => Pages\EditMovimiento::route('/{record}/edit'),
        ];
    }
}
