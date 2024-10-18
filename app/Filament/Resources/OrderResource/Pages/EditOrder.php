<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
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
