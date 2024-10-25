<?php

namespace App\Filament\Resources;

use App\Enums\MovementType;
use App\Filament\Resources\MovimientoResource\Pages;
use App\Filament\Resources\MovimientoResource\RelationManagers;
use App\Filament\Resources\MovimientoResource\RelationManagers\MovimientoProductosRelationManager;
use App\Models\Field;
use App\Models\Movimiento;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

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
                    ->searchable()
                    ->options([
                        MovementType::ENTRADA->value => 'entrada',
                        MovementType::SALIDA->value => 'salida',
                        MovementType::TRASLADO->value => 'traslado',
                    ])
                    ->required()
                    ->reactive(),

                Select::make('bodega_origen_id')
                    ->label('Origen')
                    ->relationship('bodega_origen', 'name')
                    ->options(function () {
                        $tenantId = Filament::getTenant()->id;
                        return Warehouse::where('field_id', $tenantId)->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->visible(fn ($get) => $get('tipo') !== MovementType::ENTRADA->value),

                Select::make('bodega_destino_id')
                    ->label('Destino')
                    ->relationship('bodega_destino', 'name')
                    ->options(function () {
                        $tenantId = Filament::getTenant()->id;
                        return Warehouse::where('field_id', $tenantId)->pluck('name', 'id');
                    })
                    ->required()
                    ->visible(fn ($get) => $get('tipo') !== MovementType::SALIDA->value)
                    ->searchable(),

                TextInput::make('orden_compra')
                    ->label('Orden de compra')
                    ->rules('numeric|min:1')
                    ->visible(fn ($get) => $get('tipo') == MovementType::ENTRADA->value),

                TextInput::make('nombre_proveedor')
                    ->label('Nombre del proveedor')
                    ->visible(fn ($get) => $get('tipo') == MovementType::ENTRADA->value),

                TextInput::make('guia_despacho')
                    ->label('Guía de despacho')
                    ->visible(fn ($get) => $get('tipo') == MovementType::ENTRADA->value),

                TextInput::make('comprobante')
                    ->label('Comprobante')
                    ->visible(fn ($get) => $get('tipo') !== MovementType::ENTRADA->value)
                    ->rules('numeric|min:1'),

                TextInput::make('encargado')
                    ->label('Encargado')
                    ->visible(fn ($get) => $get('tipo') !== MovementType::ENTRADA->value)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('tipo')
            ->columns([

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->badge()
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
                Tables\Filters\SelectFilter::make('bodega')
                    ->label('Bodega')
                    ->options(
                        Warehouse::all()->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where(function ($q) use ($data) {
                                $q->where('bodega_origen_id', $data['value'])
                                    ->orWhere('bodega_destino_id', $data['value']);
                            });
                        }
                    })
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

                    ExportBulkAction::make(),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovimientoProductosRelationManager::class,
            AuditsRelationManager::class
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
