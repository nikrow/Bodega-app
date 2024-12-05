<?php

namespace App\Filament\Resources\MovimientoResource\Pages;

use App\Filament\Resources\MovimientoResource;
use App\Models\Movimiento;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditMovimiento extends EditRecord
{
    protected static string $resource = MovimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Crear movimiento nuevo')
                ->color('primary'),
            Actions\Action::make('complete')
                ->label('Cerrar')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->action(function (Movimiento $record) {
                    // Lógica para marcar como completado
                    $record->is_completed = true;
                    $record->save();

                    // Opcional: Registrar una entrada en los logs
                    Log::info("Movimiento ID: {$record->id} ha sido completado por el usuario ID: " . Auth::id());

                    $tenant = Filament::getTenant();

                    return redirect()->route('filament.campo.resources.movimientos.index', ['tenant' => $tenant->slug]);

                })
                ->hidden(fn(Movimiento $record) => $record->is_completed),
        ];
    }
}
