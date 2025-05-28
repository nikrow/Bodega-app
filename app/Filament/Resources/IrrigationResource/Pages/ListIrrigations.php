<?php

namespace App\Filament\Resources\IrrigationResource\Pages;

use App\Filament\Resources\IrrigationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIrrigations extends ListRecords
{
    protected static string $resource = IrrigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Agregar Riego'),
        ];
    }
}
