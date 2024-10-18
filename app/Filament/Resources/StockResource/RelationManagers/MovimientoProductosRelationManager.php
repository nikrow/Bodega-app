<?php

namespace App\Filament\Resources\StockResource\RelationManagers;

use App\Enums\MovementType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;

class MovimientoProductosRelationManager extends RelationManager
{
    protected static string $relationship = 'MovimientoProductos';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('producto_id')
            ->columns([
                Tables\Columns\TextColumn::make('movimiento.tipo')
                    ->label('Tipo de Movimiento')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
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
                Tables\Columns\TextColumn::make('movimiento.movement_number')
                    ->label('Número de Movimiento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad Movida')
                    ->numeric(decimalPlaces: 0,thousandsSeparator: '.')
                    ->formatStateUsing(function ($record) {
                        if ($record->movimiento->tipo === MovementType::SALIDA ||
                            ($record->movimiento->tipo === MovementType::TRASLADO && $record->movimiento->bodega_origen_id === $this->getOwnerRecord()->wharehouse_id)) {
                            return -1 * $record->cantidad;
                        }
                        return $record->cantidad;
                    }),
                Tables\Columns\TextColumn::make('movimiento.bodega_origen.name')
                    ->label('Bodega Origen')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('movimiento.bodega_destino.name')
                    ->label('Bodega Destino')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('movimiento.created_at')
                    ->label('Fecha de Movimiento')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bodega')
                    ->label('Bodega')
                    ->options(
                        function () {
                            // Seleccionamos todas las bodegas para el filtro
                            return \App\Models\Wharehouse::all()->pluck('name', 'id');
                        }
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            // Filtrar los movimientos donde la bodega sea igual a la seleccionada
                            $query->whereHas('movimiento', function (Builder $query) use ($data) {
                                $query->where(function ($query) use ($data) {
                                    $query->where('bodega_origen_id', $data['value'])
                                        ->orWhere('bodega_destino_id', $data['value']);
                                });
                            });
                        }
                    })
                    ->default(fn () => $this->getOwnerRecord()->wharehouse_id)
            ])
            ;
    }
}
