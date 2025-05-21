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
            Actions\DeleteAction::make(),
        ];
    }
}
