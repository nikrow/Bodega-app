<?php

namespace App\Filament\Resources\OperatorAssignmentResource\Pages;

use App\Models\Tractor;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\OperatorAssignmentResource;

class CreateOperatorAssignment extends CreateRecord
{
    protected static string $resource = OperatorAssignmentResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        if ($tractorId = $this->data['tractor_id']) {
            Tractor::where('id', $tractorId)->update(['operator_id' => $record->id]);
        }
        if ($machineries = $this->data['machineries']) {
            $record->assignedMachineries()->sync($machineries);
        }
    }
}