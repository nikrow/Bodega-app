<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Models\Order;
use Filament\Actions;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\OrderResource;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
            Actions\Action::make('complete')
                ->label('Cerrar')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('observations')
                        ->label('Resumen de la orden')
                        ->required(),
                ])
                ->action(function (array $data, Order $record) {
                    try {
                        $record->observations = $data['observations'];
                        $record->is_completed = true;
                        $record->save();
                
                        Log::info("Orden ID: {$record->id} ha sido completada por el usuario ID: " . Auth::id());
                    } catch (\Exception $e) {
                        Log::error("Error al guardar la orden: {$e->getMessage()}");
                    }
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
