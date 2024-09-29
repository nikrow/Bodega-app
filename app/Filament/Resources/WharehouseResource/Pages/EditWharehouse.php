<?php

namespace App\Filament\Resources\WharehouseResource\Pages;

use App\Filament\Resources\WharehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWharehouse extends EditRecord
{
    protected static string $resource = WharehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
