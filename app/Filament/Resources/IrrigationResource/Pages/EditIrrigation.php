<?php

namespace App\Filament\Resources\IrrigationResource\Pages;

use App\Filament\Resources\IrrigationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIrrigation extends EditRecord
{
    protected static string $resource = IrrigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn ($record) => $record->deleted_at !== null)
                ->before(function ($action, $record) {
                    if ($record->fertilization()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Se debe eliminar la fertilización primero.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
                    ];
    }
}
