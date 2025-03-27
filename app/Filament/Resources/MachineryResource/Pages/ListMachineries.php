<?php

namespace App\Filament\Resources\MachineryResource\Pages;

use App\Filament\Resources\MachineryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMachineries extends ListRecords
{
    protected static string $resource = MachineryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
