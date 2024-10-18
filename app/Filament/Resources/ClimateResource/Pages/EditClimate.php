<?php

namespace App\Filament\Resources\ClimateResource\Pages;

use App\Filament\Resources\ClimateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClimate extends EditRecord
{
    protected static string $resource = ClimateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
