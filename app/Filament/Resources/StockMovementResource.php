<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'carbon-report';
    protected static ?string $navigationLabel = 'Movimientos Stock';
    protected static ?string $modelLabel = 'Movimientos Stock';
    protected static ?string $navigationGroup = 'Informes';
    protected static ?int $navigationSort = 1;


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                TextColumn::make('movimiento.id')
                    ->label('ID'),
                TextColumn::make('movement_type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'salida',
                        'warning' => 'traslado',
                        'primary' => 'application_usage',
                        'secondary' => 'application_usage_update',
                        'error' => 'application_usage_deleted',
                    ])
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make("producto.product_name")
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('Bodega')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity_change')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('order_number')
                    ->label('Orden')
                    ->sortable()
                    ->searchable()
                    ->default('-')
                    ->tooltip(fn($record) => $record->order_number ? "Orden: {$record->order_number}" : null),
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('user.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'traslado' => 'Traslado',
                        'preparacion' => 'Preparacion',
                    ])
                    ->label('Tipo de Movimiento'),
                Tables\Filters\Filter::make('fecha')
                    ->columns(2)
                    ->form([
                        DatePicker::make('start_date')->label('Fecha Inicio'),
                        DatePicker::make('end_date')
                            ->default(now())
                            ->label('Fecha Fin'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('created_at', '<=', $data['end_date']));
                    }),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Bodega')
                    ->options(Warehouse::regular()->pluck('name', 'id')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->actions([

            ])
            ->bulkActions([
                ExportBulkAction::make(),
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
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
