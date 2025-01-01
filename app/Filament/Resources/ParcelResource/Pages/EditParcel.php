<?php

namespace App\Filament\Resources\ParcelResource\Pages;

use App\Enums\RoleType;
use App\Filament\Resources\ParcelResource;
use App\Models\Parcel;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditParcel extends EditRecord
{
    protected static string $resource = ParcelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [
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
                ->visible(function () {
                    $user = Auth::user();
                    return in_array($user->role, [
                        RoleType::ADMIN->value,
                    ]);
                })
                ->hidden(fn ($record) => !$record->is_active),
        ];
    }
}
