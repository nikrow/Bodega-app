<?php

namespace App\Filament\Resources\MovimientoResource\Pages;

use App\Filament\Resources\MovimientoResource;
use App\Models\Movimiento;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMovimiento extends ViewRecord
{
    protected static string $resource = MovimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Crear movimiento nuevo')
                ->color('primary'),
            Actions\EditAction::make()
                ->label('Editar')
                ->hidden(fn(Movimiento $record) => $record->is_completed)
                ->color('warning')
                ->icon('heroicon-o-pencil'),
        ];
    }
}
