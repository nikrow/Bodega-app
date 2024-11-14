<?php

namespace App\Filament\Resources;

use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ConsolidatedOrderResource\Pages;

class ConsolidatedOrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'carbon-report';
    protected static ?string $navigationLabel = 'Registro de Aplicaciones';
    protected static ?string $slug = 'consolidated-orders';
    protected static ?string $modelLabel = 'Consolidado';
    protected static ?string $navigationGroup = 'Informes';

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('orderNumber')
                    ->label('Número de Orden')
                    ->sortable()
                    ->searchable(),
                TAbles\Columns\TextColumn::make('orderApplications.created_at')
                    ->label('Fecha Aplicación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                // Información del cultivo
                Tables\Columns\TextColumn::make('crop.especie')
                    ->label('Cultivo')
                    ->sortable()
                    ->searchable(),

                // Información sobre las aplicaciones
                Tables\Columns\TextColumn::make('orderApplications.id')
                    ->label('ID Aplicación')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('orderApplications.parcel.name')
                    ->label('Cuartel Aplicado')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('orderApplications.liter')
                    ->label('Litros aplicados')
                    ->sortable()
                    ->numeric()
                    ->suffix(' l'),
                Tables\Columns\TextColumn::make('applicationUsage.order.wetting')
                    ->label('Mojamiento')
                    ->numeric(thousandsSeparator:'.')
                    ->sortable()
                    ->searchable(),

                // Información de productos aplicados
                Tables\Columns\TextColumn::make('applicationUsage.product.product_name')
                    ->label('Producto Aplicado')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('applicationUsage.product.active_ingredients')
                    ->label('Ingredientes activos')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('applicationUsage.dose_per_100l')
                    ->label('Dosis')
                    ->numeric()
                    ->suffix(' l/100l'),

                Tables\Columns\TextColumn::make('applicationUsage.product_usage')
                    ->label('Producto Utilizado')
                    ->numeric()
                    ->sortable()
                    ->suffix(' kg'),

                // Responsable de la orden
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Encargado técnico')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // Filtros para estado de la orden
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'enproceso' => 'En proceso',
                        'completo' => 'Completo',
                        'cancelado' => 'Cancelado',
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsolidatedOrders::route('/'),
            'view' => Pages\ViewConsolidatedOrder::route('/{record}'),
        ];
    }
}
