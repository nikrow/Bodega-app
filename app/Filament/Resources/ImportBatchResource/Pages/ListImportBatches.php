<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use Filament\Actions;
use App\Jobs\ProcessEmailAttachments;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ImportBatchResource;

class ListImportBatches extends ListRecords
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process-emails')
                ->label('Procesar Correos Manualmente')
                ->icon('heroicon-o-envelope')
                ->action(function () {
                    ProcessEmailAttachments::dispatch();
                    Notification::make()
                        ->title('Job iniciado')
                        ->body('El procesamiento de correos ha sido iniciado.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Confirmar procesamiento de correos')
                ->modalDescription('¿Estás seguro de que deseas ejecutar el procesamiento manual de correos? Esto revisará los correos no leídos en busca de adjuntos.')
                ->modalSubmitActionLabel('Procesar'),
        ];
    }
}
