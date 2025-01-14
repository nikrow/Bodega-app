<?php
namespace App\Filament\Resources\StockResource\RelationManagers;

use App\Enums\MovementType;
use Carbon\Carbon;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class MovimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientos';

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                TextColumn::make('movement_number')
                    ->label('Número de Movimiento')
                    ->sortable()
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('producto.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Bodega')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Cantidad')
                    ->getStateUsing(function ($record) {
                        $negativeTypes = [
                            MovementType::SALIDA->value,
                            MovementType::TRASLADO_SALIDA->value,
                            MovementType::PREPARACION->value,
                        ];

                        return in_array($record->movement_type, $negativeTypes)
                            ? -abs($record->quantity_change)
                            : abs($record->quantity_change);
                    })
                    ->numeric(),
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Orden'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Opcional: Agregar filtros para fechas, tipo de movimiento, etc.
            ])
            ->actions([
                // Opcional: Acciones individuales por registro
            ])
            ->bulkActions([
                ExportBulkAction::make()->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->ignoreFormatting(['quantity_change'])
                        ->withFilename(date('Y-m-d') . ' - Movimientos productos ')
                        ->withWriterType(Excel::XLSX)
                        ->withColumns([
                            Column::make('created_at')->heading('Fecha')
                                ->formatStateUsing(function ($state) {
                                    $date = Carbon::parse($state);
                                    return Date::PHPToExcel($date);
                                })
                                ->format(NumberFormat::FORMAT_DATE_DDMMYYYY),
                            Column::make('id')->heading('ID'),
                            Column::make('producto.product_name')->heading('Producto'),
                            Column::make('warehouse.name')->heading('Bodega'),
                            Column::make('quantity_change')->heading('Cantidad')
                                ->format(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1)
                                ->getStateUsing(function ($record) {
                                    $negativeTypes = [
                                        MovementType::SALIDA->value,
                                        MovementType::PREPARACION->value,
                                    ];
                                    return in_array($record->movement_type, $negativeTypes)
                                        ? -abs($record->quantity_change)
                                        : abs($record->quantity_change);
                                }),
                            Column::make('order_number')->heading('Orden'),
                            Column::make('description')->heading('Descripción'),
                            Column::make('user.name')->heading('Creado por'),
                            Column::make('updated_by.name')->heading('Modificado por'),
                            ])
                        ]),
            ]);

    }


}
