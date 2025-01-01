<?php

namespace App\Filament\Resources\ParcelResource\Pages;

use App\Filament\Resources\ParcelResource;
use App\Models\Parcel;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class ViewParcel extends ViewRecord
{
    protected static string $resource = ParcelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->label('Editar')
                ->color('warning')
                ->visible(fn ($record) => $record->is_active),
            Actions\Action::make('darDeBaja')
                ->label('Dar de Baja')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Motivo de Baja')
                ->form([
                    Forms\Components\Textarea::make('deactivation_reason')
                        ->label('Motivo')
                        ->required(),
                ])
                ->action(function (Parcel $record, array $data) {
                    $record->update([
                        'is_active' => false,
                        'deactivated_at' => now(),
                        'deactivated_by' => Auth::id(),
                        'deactivation_reason' => $data['deactivation_reason'],
                    ]);
                })
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->is_active),

        ];


    }
}
