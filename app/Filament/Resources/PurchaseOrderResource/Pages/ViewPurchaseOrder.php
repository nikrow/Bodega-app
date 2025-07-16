<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información de la Orden')
                    ->schema([
                        TextEntry::make('number')
                            ->label('Número de Orden'),
                        TextEntry::make('provider.name')
                            ->label('Proveedor'),
                        TextEntry::make('date')
                            ->label('Fecha de Creación')
                            ->date('d/m/Y'),
                        TextEntry::make('status')
                            ->label('Estado'),
                        TextEntry::make('porcentaje_recepcion')
                            ->label('% Recepción')
                            ->getStateUsing(function ($record) {
                                $cantidadOrdenada = $record->PurchaseOrderDetails()->sum('quantity');
                                $cantidadRecibida = $record->movimientoProductos()->sum('cantidad');
                                return $cantidadOrdenada > 0 ? number_format(($cantidadRecibida / $cantidadOrdenada) * 100, 2) . '%' : '0%';
                            }),
                        TextEntry::make('observation')
                            ->label('Observación'),
                    ])->columns(2),

                Section::make('Detalles de la Orden')
                    ->schema([
                        RepeatableEntry::make('PurchaseOrderDetails')
                            ->label('Detalles')
                            ->schema([
                                TextEntry::make('product.product_name')
                                    ->label('Producto'),
                                TextEntry::make('quantity')
                                    ->label('Cantidad'),
                                TextEntry::make('price')
                                    ->label('Precio USD')
                                    ->money('USD'),
                                TextEntry::make('observation')
                                    ->label('Observación'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Movimientos de Entrada')
                    ->schema([
                        RepeatableEntry::make('movimientoProductos')
                            ->label('Movimientos')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Fecha de Entrada')
                                    ->date('d/m/Y'),
                                TextEntry::make('movimiento.guia_despacho')
                                    ->label('Guía de Despacho'),
                                TextEntry::make('producto.product_name')
                                    ->label('Producto'),
                                TextEntry::make('cantidad')
                                    ->label('Cantidad'),
                                TextEntry::make('precio_compra')
                                    ->label('Precio de Compra')
                                    ->money('USD'),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money('USD'),
                                TextEntry::make('unidad_medida')
                                    ->label('Unidad de Medida'),
                                TextEntry::make('lot_number')
                                    ->label('Número de Lote')
                                    ->visible(fn ($record) => $record->producto?->requiresBatchControl()),
                                TextEntry::make('expiration_date')
                                    ->label('Fecha de Expiración')
                                    ->date('d/m/Y')
                                    ->visible(fn ($record) => $record->producto?->requiresBatchControl()),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
