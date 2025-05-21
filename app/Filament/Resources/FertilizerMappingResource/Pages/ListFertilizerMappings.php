<?php

namespace App\Filament\Resources\FertilizerMappingResource\Pages;

use App\Filament\Resources\FertilizerMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFertilizerMappings extends ListRecords
{
    protected static string $resource = FertilizerMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
