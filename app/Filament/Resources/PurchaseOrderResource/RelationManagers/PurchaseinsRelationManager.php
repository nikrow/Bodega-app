<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\MovementType;
use App\Models\MovimientoProducto;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\RelationManagers\RelationManager;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PurchaseinsRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientoProductos';

    protected static ?string $title = 'Movimientos de Entrada';
    protected static ?string $modelLabel = 'Movimiento de Entrada';
    protected static ?string $pluralModelLabel = 'Movimientos de Entrada';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Entrada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('movimiento.guia_despacho')
                    ->label('Guía de Despacho')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('producto.product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_compra')
                    ->label('Precio de Compra')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad de Medida')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lot_number')
                    ->label('Número de Lote')
                    ->sortable()
                    ->visible(fn () => $this->ownerRecord->movimientoProductos->first()?->producto?->requiresBatchControl()),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Fecha de Expiración')
                    ->date('d/m/Y')
                    ->sortable()
                    ->visible(fn () => $this->ownerRecord->movimientoProductos->first()?->producto?->requiresBatchControl()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
                ExportBulkAction::make()
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->whereHas('movimiento', function (Builder $q) {
                    $q->where('purchase_order_id', $this->ownerRecord->id)
                      ->where('tipo', MovementType::ENTRADA);
                });
            });
    }
}