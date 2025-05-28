<?php

namespace App\Filament\Resources\IrrigationResource\Pages;

use App\Filament\Resources\IrrigationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIrrigation extends ViewRecord
{
    protected static string $resource = IrrigationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
