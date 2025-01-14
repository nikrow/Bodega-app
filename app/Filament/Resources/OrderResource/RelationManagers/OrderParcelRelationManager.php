<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderApplication;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class OrderParcelRelationManager extends RelationManager
{
    protected static string $relationship = 'parcels';

    protected static ?string $title = 'Avance cuarteles';

    protected static ?string $recordTitleAttribute = 'Cuartel';

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cuartel')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('surface')
                    ->label('Superficie (ha)')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('applied_percentage')
                    ->label('Porcentaje aplicado')
                    ->suffix('%')
                    ->getStateUsing(function ($record) {
                        $order = $this->ownerRecord; // La orden actual
                        $parcelSurface = $record->surface ?? 0;

                        // Obtener la superficie total aplicada en este cuartel para esta orden
                        $totalSurfaceApplied = OrderApplication::where('order_id', $order->id)
                            ->where('parcel_id', $record->id)
                            ->sum('surface');

                        if ($parcelSurface > 0) {
                            $percentage = ($totalSurfaceApplied / $parcelSurface) * 100;
                            return round($percentage, 3);
                        }

                        return 0;
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->actions([
                // Acciones por registro
            ])
            ->bulkActions([
                ExportBulkAction::make(),
            ]);
    }
}
