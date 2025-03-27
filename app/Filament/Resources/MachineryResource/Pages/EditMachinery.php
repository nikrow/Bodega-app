<?php

namespace App\Filament\Resources\MachineryResource\Pages;

use App\Filament\Resources\MachineryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMachinery extends EditRecord
{
    protected static string $resource = MachineryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
