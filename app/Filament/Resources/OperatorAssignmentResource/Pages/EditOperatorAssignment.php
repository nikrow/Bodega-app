<?php
namespace App\Filament\Resources\OperatorAssignmentResource\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\OperatorAssignmentResource;
use Illuminate\Support\Facades\Log;

class EditOperatorAssignment extends EditRecord
{
    protected static string $resource = OperatorAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Obtener los valores de tractores y maquinarias del formulario
        $tractors = $data['tractors'] ?? [];
        $machineries = $data['machineries'] ?? [];

        // Obtener el usuario asociado al registro
        $user = $this->record->user;

        if ($user) {
            // Sincronizar manualmente las relaciones del usuario
            $user->assignedTractors()->sync($tractors);
            $user->assignedMachineries()->sync($machineries);
        } else {
            // Registrar un error si no hay usuario asociado
            Log::error('No user associated with OperatorAssignment:', ['record_id' => $this->record->id]);
            throw new \Exception('No se puede guardar: No hay un operario asociado.');
        }

        // Eliminar tractors y machineries de los datos para evitar que Filament los trate como atributos
        unset($data['tractors'], $data['machineries']);

        return $data;
    }
}
