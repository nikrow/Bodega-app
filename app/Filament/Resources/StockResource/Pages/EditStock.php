<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Models\User;
use Filament\Pages\Actions;

use Filament\Pages\Actions\ButtonAction;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStock extends EditRecord
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (User $user) => $user->can('delete field')),
            ButtonAction::make('save')
                ->label('Guardar')
                ->visible(fn (User $user) => $user->can('edit field')),
        ];
    }
}
