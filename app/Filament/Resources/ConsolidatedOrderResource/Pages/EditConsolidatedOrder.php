<?php

namespace App\Filament\Resources\ConsolidatedOrderResource\Pages;

use App\Filament\Resources\ConsolidatedOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsolidatedOrder extends EditRecord
{
    protected static string $resource = ConsolidatedOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
