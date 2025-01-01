<?php

namespace App\Filament\Resources\ParcelResource\RelationManagers;


use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class AplicacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'applicationUsages'; // Relación definida en el modelo Parcel
    protected static ?string $title = 'Aplicaciones';
    protected static ?string $modelLabel = 'Aplicación';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Aplicación')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.orderNumber')
                    ->label('Número de Orden')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.active_ingredients')
                    ->label('Ingrediente Activo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('liters_applied')
                    ->label('Litros Aplicados')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dose_per_100l')
                    ->label('Dosis (l/100l)')
                    ->numeric(),
                Tables\Columns\TextColumn::make('product_usage')
                    ->label('Producto Utilizado')
                    ->numeric(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo Total')
                    ->numeric(),
            ])
            ->filters([
                // Opcional: Añade filtros si es necesario
            ])
            ->headerActions([
                // Acciones de cabecera, si las necesitas
            ])
            ->actions([
                // Acciones individuales por registro, si las necesitas
            ])
            ->bulkActions([
                ExportBulkAction::make()->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(date('Y-m-d') . ' - Export Registro de aplicaciones')
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('created_at')->heading('Fecha')
                                ->formatStateUsing(function ($state) {
                                    $date = \Carbon\Carbon::parse($state);
                                    return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($date);
                                })
                                ->format(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY),
                            Column::make('id')->heading('ID'),
                            Column::make('parcel.name')->heading('Cuartel'),
                            Column::make('order.objective')->heading('Objetivo'),
                            Column::make('parcel.crop.especie')->heading('Cultivo'),
                            Column::make('order.orderNumber')->heading('Número de orden'),
                            Column::make('product.product_name')->heading('Producto'),
                            Column::make('product.active_ingredients')->heading('Ingrediente activo'),
                            Column::make('product.waiting_time')->heading('Carencia'),
                            Column::make('product.reentry')->heading('Fecha de Reingreso'),
                            Column::make('harvest_reentry')->heading('Reanudar Cosecha'),
                            Column::make('dose_per_100l')->heading('Dosis L/100'),
                            Column::make('order.wetting')->heading('Mojamiento L/Ha'),
                            Column::make('liters_applied')->heading('Litros aplicados'),
                            Column::make('orderApplication.surface')->heading('Superficie aplicada'),
                            Column::make('product_usage')->heading('Producto utilizado'),
                            Column::make('order.equipment')->heading('Equipamiento usado'),
                            Column::make('applicators_details')->heading('Aplicadores'),
                            Column::make('order.user.name')->heading('Encargado'),
                            Column::make('orderApplication.temperature')->heading('Temperatura °C'),
                            Column::make('orderApplication.wind_speed')->heading('Velocidad del viento km/hr'),
                            Column::make('orderApplication.moisture')->heading('Humedad %'),
                            Column::make('total_cost')->heading('Costo aplicación USD'),
                            ]),
                ]),
            ]);
    }
}
