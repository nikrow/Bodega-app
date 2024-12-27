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
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderNumber')
                    ->label('Número de orden')
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
                    ->numeric()
                    ->suffix('l/100l')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_usage')
                    ->label('Producto utilizado')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo aplicación')
                    ->numeric(),
                Tables\Columns\TextColumn::make('order.wetting')
                    ->label('Mojamiento')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.waiting_time')
                    ->label('Carencia'),
                Tables\Columns\TextColumn::make('product.reentry')
                    ->label('Reingreso')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harvest_reentry')
                    ->label('Reingreso Cosecha')
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
                ExportBulkAction::make(),
            ]);
    }
}
