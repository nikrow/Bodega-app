<?php

namespace App\Filament\Resources\FertilizationResource\Pages;

use App\Filament\Resources\FertilizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFertilizations extends ListRecords
{
    protected static string $resource = FertilizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
