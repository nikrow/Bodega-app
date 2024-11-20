<?php

namespace App\Filament\Resources\PreparationResource\Pages;

use App\Filament\Resources\PreparationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPreparations extends ListRecords
{
    protected static string $resource = PreparationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
