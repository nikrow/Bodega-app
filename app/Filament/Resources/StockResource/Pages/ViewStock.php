<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewStock extends ViewRecord
{
    protected static string $resource = StockResource::class;
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
        ->schema([
            Section::make('Cantidad')
                ->columns(2)
                ->schema([
                    TextEntry::make('quantity')
                        ->label('Cantidad Disponible')
                        ->numeric(decimalPlaces: 1, thousandsSeparator: '.')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->color("primary"),
                    TextEntry::make('product.product_name')
                        ->label('Producto')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold),
                    TextEntry::make('updated_at')
                        ->label('Última Actualización'),

            ]),
            Section::make('Detalles')
                ->collapsed()
                ->schema([
                    TextEntry::make('warehouse.name')
                        ->label('Bodega'),
                    TextEntry::make('product.family')
                        ->label('Grupo')
                ])

        ]);
        }
  }
