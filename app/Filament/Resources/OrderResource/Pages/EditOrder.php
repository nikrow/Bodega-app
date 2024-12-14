<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Descargar PDF')
                ->icon('fas-download')
                ->action('downloadPdf'),
            Actions\Action::make('complete')
                ->label('Cerrar')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function (Order $record) {
                    // Lógica para marcar como completado
                    $record->is_completed = true;
                    $record->save();

                    // Opcional: Registrar una entrada en los logs
                    Log::info("Movimiento ID: {$record->id} ha sido completado por el usuario ID: " . Auth::id());
                })
                ->hidden(fn(Order $record) => $record->is_completed),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
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
