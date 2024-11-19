<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;

use App\Models\StockMovement;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
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
            ->columns([
                TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('movement_type')
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
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'traslado' => 'Traslado',
                        'application_usage' => 'Uso de Aplicación',
                        'application_usage_update' => 'Actualización de Uso de Aplicación',
                        'application_usage_deleted' => 'Eliminación de Uso de Aplicación',
                    ])
                    ->label('Tipo de Movimiento'),
                Tables\Filters\Filter::make('fecha')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')->label('Fecha Inicio'),
                        Forms\Components\DatePicker::make('end_date')->label('Fecha Fin'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn ($q) => $q->whereDate('created_at', '<=', $data['end_date']));
                    }),
            ])
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
