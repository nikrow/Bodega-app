<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->color('warning')
                ->hidden(fn (Order $record) => $record->is_completed)
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->visible(fn(Order $record) => $record->orderLines()->count() === 0),
            Actions\Action::make('downloadPdf')
                ->label('Orden')
                ->color('danger')
                ->icon('phosphor-file-pdf-fill')
                ->url(fn(Order $record) => route('orders.downloadPdf', $record->id))
                ->openUrlInNewTab(),
            Actions\Action::make('bodegaPdf')
                ->label('Bodega')
                ->color('warning')
                ->icon('phosphor-file-pdf-duotone')
                ->url(fn(Order $record) => route('orders.bodegaPdf', $record->id))
                ->openUrlInNewTab(),
        ];
    }

}
