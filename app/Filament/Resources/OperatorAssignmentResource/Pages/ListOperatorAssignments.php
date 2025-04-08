<?php

namespace App\Filament\Resources\OperatorAssignmentResource\Pages;

use App\Filament\Resources\OperatorAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOperatorAssignments extends ListRecords
{
    protected static string $resource = OperatorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
