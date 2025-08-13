<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use Filament\Actions;
use App\Models\Program;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ProgramResource;

class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (Program $record) => $record->is_active),
        ];
    }
}
