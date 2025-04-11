<?php

namespace App\Filament\Resources\OperatorAssignmentResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\OperatorAssignmentResource;

class EditOperatorAssignment extends EditRecord
{
    protected static string $resource = OperatorAssignmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Forzar un cambio en updated_at para que el evento updating se dispare
        $data['updated_at'] = now();
        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $user = $this->record->user;

        Log::info('Guardando relaciones de OperatorAssignment', [
            'user_id' => $user->id,
            'tractors' => $data['tractors'] ?? [],
            'machineries' => $data['machineries'] ?? []
        ]);

        if ($user) {
            DB::transaction(function () use ($user, $data) {
                $user->assignedTractors()->sync($data['tractors'] ?? []);
                $user->assignedMachineries()->sync($data['machineries'] ?? []);
            });
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}