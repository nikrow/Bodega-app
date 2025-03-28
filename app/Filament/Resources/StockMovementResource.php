<?php

namespace App\Filament\Resources;

use App\Enums\MovementType;
use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

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
                TextColumn::make('related_id')
                    ->sortable()
                    ->searchable()
                    ->label('ID'),
                TextColumn::make('movement_type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return MovementType::tryFrom($record->movement_type)?->getLabel();
                    })
                    ->color(function ($record) {
                        return MovementType::tryFrom($record->movement_type)?->getColor();
                    }),
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
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $negativeTypes = [
                            MovementType::SALIDA->value,
                            MovementType::TRASLADO_SALIDA->value,
                            MovementType::PREPARACION->value,
                        ];

                        return in_array($record->movement_type, $negativeTypes)
                            ? -abs($record->quantity_change)
                            : abs($record->quantity_change);
                    }),
        TextColumn::make('order_number')
                    ->label('Orden')
                    ->sortable()
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
                        'preparacion' => 'Preparacion',
                        'traslado-salida' => 'Traslado Salida',
                        'traslado-entrada' => 'Traslado Entrada',
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
                ExportBulkAction::make()->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->ignoreFormatting(['quantity_change'])
                        ->withFilename(date('Y-m-d') . ' - export')
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('created_at')->heading('Fecha')
                                ->formatStateUsing(function ($state) {
                                    $date = \Carbon\Carbon::parse($state);
                                    return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($date);
                                })
                                ->format(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY),
                            Column::make('movimiento.id')->heading('ID'),
                            Column::make('movement_type')->heading('Tipo'),
                            Column::make('producto.product_name')->heading('Producto'),
                            Column::make('warehouse.name')->heading('Bodega'),
                            Column::make('quantity_change')->heading('Cantidad')
                                ->format(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1)
                                ->getStateUsing(function ($record) {
                                    $negativeTypes = [
                                        MovementType::SALIDA->value,
                                        MovementType::TRASLADO_SALIDA->value,
                                        MovementType::PREPARACION->value,
                                    ];

                                    return in_array($record->movement_type, $negativeTypes)
                                        ? -abs($record->quantity_change)
                                        : abs($record->quantity_change);
                                }),
                            Column::make('order_number')->heading('Orden'),
                            Column::make('description')->heading('Descripción'),

                        ]),
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
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
