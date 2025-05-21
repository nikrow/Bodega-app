<?php

namespace App\Filament\Resources\FertilizationResource\Pages;

use App\Filament\Resources\FertilizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFertilization extends EditRecord
{
    protected static string $resource = FertilizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
