<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationRecordResource\Pages;
use App\Models\OrderApplicationUsage;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

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
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('id')
                    ->label('ID Aplicación')
                    ->sortable()
                    ->searchable(),
                // Cuartel
                TextColumn::make('parcel.name')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),

                // Número de orden
                TextColumn::make('order.orderNumber')
                    ->label('Número de orden')
                    ->sortable()
                    ->searchable(),

                // Producto
                TextColumn::make('product.product_name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),

                // Ingrediente activo
                TextColumn::make('product.active_ingredients')
                    ->label('Ingrediente activo')
                    ->sortable()
                    ->searchable(),

                // Carencia
                TextColumn::make('product.waiting_time')
                    ->label('Carencia')
                    ->sortable()
                    ->searchable(),

                // Fecha de reingreso
                TextColumn::make('reentry_date')
                    ->label('Fecha de reingreso')
                    ->getStateUsing(function ($record) {
                        $createdDate = $record->created_at ? $record->created_at->copy() : now();
                        $reentryDays = $record->product->reentry ?? 0;
                        return $createdDate->addDays($reentryDays);
                    })
                    ->date('d/m/Y')
                    ->sortable(),

                // Reanudar cosecha
                TextColumn::make('harvest_resume_date')
                    ->label('Reanudar cosecha')
                    ->getStateUsing(function ($record) {
                        $createdDate = $record->created_at ? $record->created_at->copy() : now();
                        $waitingTimeDays = $record->product->waiting_time ?? 0;
                        return $createdDate->addDays($waitingTimeDays);
                    })
                    ->date('d/m/Y')
                    ->sortable(),

                // Dosis l/100 lt
                TextColumn::make('dose_per_100l')
                    ->label('Dosis l/100lt')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' l/100l')
                    ->sortable()
                    ->searchable(),

                // Mojamiento
                TextColumn::make('order.wetting')
                    ->label('Mojamiento')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' l/ha')
                    ->sortable()
                    ->searchable(),

                // Litros aplicados
                TextColumn::make('liters_applied')
                    ->label('Litros aplicados')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' l')
                    ->sortable()
                    ->searchable(),

                // Cantidad de producto utilizado
                TextColumn::make('product_usage')
                    ->label('Producto utilizado')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' l')
                    ->sortable()
                    ->searchable(),

                // Equipamiento usado
                TextColumn::make('order.equipment')
                    ->label('Equipamiento usado')
                    ->formatStateUsing(function ($state) {
                        return is_array($state) ? implode(', ', $state) : $state;
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicators_details')
                    ->label('Aplicadores')
                    ->searchable(),
                // Encargado
                TextColumn::make('order.user.name')
                    ->label('Encargado')
                    ->sortable()
                    ->searchable(),

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

                // Temperatura
                TextColumn::make('orderApplication.temperature')
                    ->label('Temperatura')
                    ->suffix(' °C')
                    ->numeric()
                    ->sortable()
                    ->searchable(),

                // Velocidad del viento
                TextColumn::make('orderApplication.wind_speed')
                    ->label('Velocidad del viento')
                    ->suffix(' km/h')
                    ->numeric()
                    ->sortable()
                    ->searchable(),

                // Humedad
                TextColumn::make('orderApplication.moisture')
                    ->label('Humedad')
                    ->suffix(' %')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // Puedes agregar filtros si lo deseas
            ])
            ->actions([
                // Acciones por registro
            ])
            ->bulkActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()
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
