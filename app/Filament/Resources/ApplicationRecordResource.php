<?php

namespace App\Filament\Resources;

use App\Enums\FamilyType;
use App\Filament\Resources\ApplicationRecordResource\Pages;
use App\Models\OrderApplicationUsage;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;

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
                    ->sortable(),
                TextColumn::make('id')
                    ->label('ID Aplicación')
                    ->sortable(),
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
                TextColumn::make('order.orderNumber')
                    ->label('Número de orden')
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
                    ->sortable(),

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
                TextColumn::make('orderApplication.moisture'),

                TextColumn::make('total_cost')
                    ->label('Costo aplicación')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: '.', decimalSeparator: ',')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->prefix('USD')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_family')
                    ->label('Grupo')
                    ->options([
                        FamilyType::INSECTICIDA->value => 'Insecticida',
                        FamilyType::HERBICIDA->value => 'Herbicida',
                        FamilyType::FERTILIZANTE->value => 'Fertilizante',
                        FamilyType::ACARICIDA->value => 'Acaricida',
                        FamilyType::FUNGICIDA->value => 'Fungicida',
                        FamilyType::BIOESTIMULANTE->value => 'Bioestimulante',
                        FamilyType::REGULADOR->value => 'Regulador',
                        FamilyType::BLOQUEADOR->value => 'Bloqueador',
                        FamilyType::OTROS->value => 'Otros',
                    ])
                    ->relationship('product', 'family'),
                Tables\Filters\SelectFilter::make('parcel.crop_id')
                    ->relationship('parcel.crop', 'especie')
                    ->label('Cultivo'),
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
                            ->when($data['start_date'], fn($q) => $q->whereDate('created_at', '>=', $data['start_date']))
                            ->when($data['end_date'], fn($q) => $q->whereDate('created_at', '<=', $data['end_date']));
                    }),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
