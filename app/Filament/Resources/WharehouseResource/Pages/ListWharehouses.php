<?php

namespace App\Filament\Resources\WharehouseResource\Pages;

use App\Filament\Resources\WharehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWharehouses extends ListRecords
{
    protected static string $resource = WharehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
