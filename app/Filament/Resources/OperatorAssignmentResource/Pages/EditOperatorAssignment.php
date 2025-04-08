<?php

namespace App\Filament\Resources\OperatorAssignmentResource\Pages;

use App\Models\Tractor;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\OperatorAssignmentResource;

class EditOperatorAssignment extends EditRecord
{
    protected static string $resource = OperatorAssignmentResource::class;

    protected function beforeSave(): void
    {
        $record = $this->record;
        $newTractorId = $this->data['tractor_id'];

        // Liberar el tractor anterior si cambió
        if ($record->assignedTractor && $record->assignedTractor->id != $newTractorId) {
            Tractor::where('id', $record->assignedTractor->id)->update(['operator_id' => null]);
        }

        // Asignar el nuevo tractor
        if ($newTractorId) {
            Tractor::where('id', $newTractorId)->update(['operator_id' => $record->id]);
        }
    }
}