<?php

namespace App\Filament\Resources\OperatorAssignmentResource\Pages;

use App\Filament\Resources\OperatorAssignmentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\OperatorAssignment;

class CreateOperatorAssignment extends CreateRecord
{
    protected static string $resource = OperatorAssignmentResource::class;
}