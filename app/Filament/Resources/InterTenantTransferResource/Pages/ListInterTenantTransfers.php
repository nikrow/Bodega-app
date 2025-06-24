<?php

namespace App\Filament\Resources\InterTenantTransferResource\Pages;

use App\Filament\Resources\InterTenantTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInterTenantTransfers extends ListRecords
{
    protected static string $resource = InterTenantTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
