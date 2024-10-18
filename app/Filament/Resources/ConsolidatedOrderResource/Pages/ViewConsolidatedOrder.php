<?php

namespace App\Filament\Resources\ConsolidatedOrderResource\Pages;

use App\Filament\Resources\ConsolidatedOrderResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewConsolidatedOrder extends ViewRecord
{
    protected static string $resource = ConsolidatedOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Cantidad')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('quantity')
                            ->label('Cantidad Disponible')
                            ->numeric(decimalPlaces: 0, thousandsSeparator: '.')
                            ->size('lg') // Uso del tamaño 'lg' directamente
                            ->weight(FontWeight::Bold)
                            ->color('primary'),
                        TextEntry::make('product.product_name')
                            ->label('Producto')
                            ->size('lg') // Uso del tamaño 'lg' directamente
                            ->weight(FontWeight::Bold),
                        TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i'), // Mostrar en formato de fecha y hora
                    ]),
                Section::make('Detalles')
                    ->collapsed() // Esta sección estará colapsada por defecto
                    ->schema([
                        TextEntry::make('wharehouse.name')
                            ->label('Bodega'),
                        TextEntry::make('product.family')
                            ->label('Grupo'),
                    ]),
            ]);
    }
}
