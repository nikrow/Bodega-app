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
            Actions\Action::make('downloadPdf')
                ->label('Descargar PDF')
                ->color('danger')
                ->icon('heroicon-s-document-arrow-down')
                ->url(fn () => route('orders.downloadPdf', $this->record))
                ->openUrlInNewTab(),
        ];
    }

}
