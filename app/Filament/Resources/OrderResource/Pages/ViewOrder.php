<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ListEntry;
use Filament\Infolists\Components\TableEntry;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getInfolists(): array
    {
        return [
            'default' => Infolists\Infolist::make()
                ->schema([
                    Grid::make(['default' => 2])
                        ->schema([
                            TextEntry::make('orderNumber')
                                ->label('Número de Orden')
                                ->columnSpan(1),
                            TextEntry::make('created_at')
                                ->label('Fecha de Creación')
                                ->dateTime()
                                ->columnSpan(1),
                            TextEntry::make('user.name')
                                ->label('Encargado')
                                ->columnSpan(1),
                            TextEntry::make('status.value')
                                ->label('Estado')
                                ->columnSpan(1),
                            TextEntry::make('field.name')
                                ->label('Campo')
                                ->columnSpan(1),
                            TextEntry::make('crop.especie')
                                ->label('Cultivo')
                                ->columnSpan(1),
                        ]),

                    ListEntry::make('orderAplications')
                        ->label('Aplicaciones')
                        ->schema([
                            Grid::make(['default' => 2])
                                ->schema([
                                    TextEntry::make('parcel.name')
                                        ->label('Cuartel')
                                        ->columnSpan(1),
                                    TextEntry::make('liter')
                                        ->label('Litros Aplicados')
                                        ->columnSpan(1),
                                    TextEntry::make('wetting')
                                        ->label('Mojamiento')
                                        ->suffix('l/ha')
                                        ->columnSpan(1),
                                    TextEntry::make('surface')
                                        ->label('Superficie')
                                        ->suffix('ha')
                                        ->columnSpan(1),
                                    TextEntry::make('temperature')
                                        ->label('Temperatura')
                                        ->suffix('°C')
                                        ->columnSpan(1),
                                    TextEntry::make('moisture')
                                        ->label('Humedad')
                                        ->suffix('%')
                                        ->columnSpan(1),
                                ])
                        ]),

                    TableEntry::make('orderLines')
                        ->label('Líneas de Orden')
                        ->columns([
                            TextEntry::make('product.product_name')
                                ->label('Producto')
                                ->searchable(),
                            TextEntry::make('product.active_ingredients')
                                ->label('Ingrediente Activo'),
                            TextEntry::make('dosis')
                                ->label('Dosis')
                                ->suffix('l/100l'),
                            TextEntry::make('reasons')
                                ->label('Razón'),
                            TextEntry::make('waiting_time')
                                ->label('Carencia'),
                            TextEntry::make('reentry')
                                ->label('Reingreso'),
                        ]),
                ])
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('downloadPdf')
                ->label('Descargar PDF')
                ->icon('fas-download')
                ->action('downloadPdf'),
        ];
    }

    public function downloadPdf()
    {
        $order = $this->record;

        // Generar vista para el PDF
        $pdf = DomPDF::loadView('pdf.order', compact('order'));

        // Descargar el PDF
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'order.pdf');
    }
}
