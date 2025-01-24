<?php

namespace App\Filament\Resources;


use App\Filament\Resources\ApplicationRecordResource\Pages;

use App\Models\OrderApplicationUsage;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ApplicationRecordResource extends Resource
{
    protected static ?string $model = OrderApplicationUsage::class;

    protected static ?string $navigationIcon = 'carbon-report';
    protected static ?string $navigationLabel = 'Registro de aplicaciones';
    protected static ?string $pluralModelLabel = 'Registros de aplicaciones';
    protected static ?string $modelLabel = 'Registro de aplicación';
    protected static ?string $navigationGroup = 'Informes';
    protected static ?int $navigationSort = 2;

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->defaultSort('created_at', 'desc')
            ->columns([
                // Fecha de aplicación
                TextColumn::make('created_at')
                    ->label('Fecha aplicación')
                    ->date('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('order_application_id')
                    ->label('ID Aplicación')
                    ->sortable(),
                // Objetivo (razón)
                TextColumn::make('order.objective')
                    ->label('Objetivo')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', array_filter($state));
                        }
                        return $state;
                    })
                    ->sortable()
                    ->searchable(),
                // Cuartel
                TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('parcel.crop.especie')
                    ->label('Cultivo')
                    ->sortable()
                    ->searchable(),
                // Número de orden
                TextColumn::make('orderNumber')
                    ->label('Número de orden')
                    ->searchable()
                    ->sortable(),

                // Producto
                TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),

                // Ingrediente activo
                TextColumn::make('product.active_ingredients')
                    ->label('Ingrediente activo')
                    ->sortable(),

                // Carencia
                TextColumn::make('product.waiting_time')
                    ->label('Carencia')
                    ->suffix('  dias')
                    ->sortable(),

                // Fecha de reingreso
                TextColumn::make('reentry_date')
                    ->label('Fecha de reingreso')
                    ->getStateUsing(function ($record) {
                        $createdDate = $record->created_at ? $record->created_at->copy() : now();
                        $reentryDays = $record->product->waiting_time ?? 0;
                        return $createdDate->addDays($reentryDays);
                    })
                    ->date('d/m/Y')
                    ->sortable(),

                // Reanudar cosecha
                TextColumn::make('harvest_resume_date')
                    ->label('Reanudar cosecha')
                    ->getStateUsing(function ($record) {
                        $createdDate = $record->created_at ? $record->created_at->copy() : now();
                        $waitingTimeHour = $record->product->reentry ?? 0;
                        return $createdDate->addHours($waitingTimeHour);
                    })
                    ->date('d/m/Y H:i')
                    ->sortable(),

                // Dosis l/100 lt
                TextColumn::make('dose_per_100l')
                    ->label('Dosis l/100lt')
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' l/100l')
                    ->sortable(),

                // Mojamiento
                TextColumn::make('order.wetting')
                    ->label('Mojamiento')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' l/ha')
                    ->sortable(),

                // Litros aplicados
                TextColumn::make('liters_applied')
                    ->label('Litros aplicados')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' l')
                    ->sortable(),
                //Superficie aplicada
                TextColumn::make('orderApplication.surface')
                    ->label('Superficie aplicada')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' ha')
                    ->sortable(),
                // Cantidad de producto utilizado
                TextColumn::make('product_usage')
                    ->label('Producto utilizado')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' l')
                    ->sortable(),

                // Equipamiento usado
                TextColumn::make('order.equipment')
                    ->label('Equipamiento usado')
                    ->formatStateUsing(function ($state) {
                        return is_array($state) ? implode(', ', $state) : $state;
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicators_details')
                    ->label('Aplicadores'),
                // Encargado
                TextColumn::make('order.user.name')
                    ->label('Encargado')
                    ->sortable()
                    ->searchable(),

                // Temperatura
                TextColumn::make('orderApplication.temperature')
                    ->label('Temperatura')
                    ->suffix(' °C')
                    ->numeric()
                    ->sortable(),

                // Velocidad del viento
                TextColumn::make('orderApplication.wind_speed')
                    ->label('Velocidad del viento')
                    ->suffix(' km/h')
                    ->numeric()
                    ->sortable(),

                // Humedad
                TextColumn::make('orderApplication.moisture')
                    ->label('Humedad')
                    ->suffix(' %')
                    ->numeric()
                    ->sortable(),


                TextColumn::make('total_cost')
                    ->label('Costo aplicación')
                    ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->prefix('USD')
            ])
            ->filters([

                Tables\Filters\SelectFilter::make('parcel.crop_id')
                    ->relationship('parcel.crop', 'especie')
                    ->label('Cultivo'),

                Tables\Filters\Filter::make('fecha')
                    ->columns()
                    ->form([
                        DatePicker::make('start_date')->label('Fecha Inicio'),
                        DatePicker::make('end_date')
                            ->default(now())
                            ->label('Fecha Fin'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn($q) => $q->whereDate('created_at', '<=', $data['end_date']));
                    }),
                Tables\Filters\SelectFilter::make('orderNumber')
                    ->label('Número de orden')
                    ->searchable()
                    ->options(function () {
                        $tenantId = Filament::getTenant()->id;

                        return OrderApplicationUsage::where('field_id', $tenantId)
                            ->pluck('orderNumber', 'orderNumber')
                            ->filter()
                            ->toArray();
                    })
                    ->attribute('orderNumber')
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                // Acciones por registro
            ])
            ->bulkActions([
                ExportBulkAction::make()->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(date('Y-m-d') . ' - Export Registro de aplicaciones')
                        ->withWriterType(Excel::XLSX)
                        ->withColumns([
                            Column::make('created_at')->heading('Fecha')
                                ->formatStateUsing(function ($state) {
                                    $date = Carbon::parse($state);
                                    return Date::PHPToExcel($date);
                                })
                                ->format(NumberFormat::FORMAT_DATE_DDMMYYYY),
                            Column::make('order_application_id')->heading('ID Aplicación'),
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

    public static function getRelations(): array
    {
        return [
            // Relaciones si es necesario
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplicationRecords::route('/'),
        ];
    }
}
