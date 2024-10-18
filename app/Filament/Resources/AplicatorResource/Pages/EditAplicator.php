<?php

namespace App\Filament\Resources\AplicatorResource\Pages;

use App\Filament\Resources\AplicatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAplicator extends EditRecord
{
    protected static string $resource = AplicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
