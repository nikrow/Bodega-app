<?php

namespace App\Filament\Resources\PreparationResource\Pages;

use App\Filament\Resources\PreparationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPreparation extends EditRecord
{
    protected static string $resource = PreparationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
