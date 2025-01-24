<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ApplicationUsageRelationManager extends RelationManager
{
    protected static string $relationship = 'applicationUsage';

    protected static ?string $title = 'Cantidad producto utilizado';
    protected static ?string $modelLabel = 'Producto utilizado';


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderNumber')
                    ->label('Número de orden')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable(),
                TABLES\Columns\TextColumn::make('product.active_ingredients')
                    ->label('Ingrediente activo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('liters_applied')
                    ->label('Litros aplicados')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dose_per_100l')
                    ->label('Dosis')
                    ->numeric(3, ',', '.')
                    ->suffix('  l/100l')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_usage')
                    ->label('Producto utilizado')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo aplicación')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('order.wetting')
                    ->label('Mojamiento')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.waiting_time')
                    ->suffix('  dias')
                    ->label('Carencia'),
                
                Tables\Columns\TextColumn::make('reentry_date')
                    ->label('Reingreso a cuartel')
                    ->getStateUsing(function ($record) {
                        $createdDate = $record->created_at ? $record->created_at->copy() : now();
                        $reentryHour = $record->product->reentry ?? 0;
                        return $createdDate->addHours($reentryHour);
                    })
                    ->date('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('harvest_reentry')
                    ->label('Reanudar Cosecha')
                    ->getStateUsing(function ($record) {
                        // Calcula la fecha de reingreso a la cosecha sumando la carencia en días a la fecha de creación
                        $createdDate = $record->created_at ? $record->created_at->copy() : now();
                        $waitingTimeDays = $record->product->waiting_time ?? 0;

                        return $createdDate->addDays($waitingTimeDays);
                    })
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->actions([

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
                            Column::make('orderNumber')->heading('Número de orden'),
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
