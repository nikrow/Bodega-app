<?php

namespace App\Filament\Resources\MovimientoResource\Pages;

use App\Enums\RoleType;
use App\Filament\Resources\MovimientoResource;
use App\Models\Movimiento;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ViewMovimiento extends ViewRecord
{
    protected static string $resource = MovimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->hidden(fn(Movimiento $record) => $record->is_completed)
                ->color('warning')
                ->icon('heroicon-o-pencil'),
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
                ->visible(function () {
                    $user = Auth::user();
                    return in_array($user->role, [
                        RoleType::ADMIN->value,
                        RoleType::BODEGUERO->value,
                    ]);
                })
                ->hidden(fn(Movimiento $record) => $record->is_completed),
        ];

    }
}
