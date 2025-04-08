<?php

namespace App\Filament\Resources\ConsolidatedReportResource\Pages;

use App\Filament\Resources\ConsolidatedReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsolidatedReport extends EditRecord
{
    protected static string $resource = ConsolidatedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
