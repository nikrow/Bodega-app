<?php

namespace App\Filament\Resources\MovimientoResource\Pages;

use App\Filament\Resources\MovimientoResource;
use App\Models\Warehouse;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListMovimientos extends ListRecords
{
    protected static string $resource = MovimientoResource::class;


    public function getTabs(): array
    {
        // Obtener el campo actual (field) del usuario
        $currentFieldId = Filament::getTenant()->id;

        // Obtener todas las bodegas que pertenecen al campo actual
        $wharehouses = Warehouse::where('field_id', $currentFieldId)->get();

        // Crear una pestaña para todas las bodegas que pertenecen al campo actual
        $tabs = [
            'Todas' => Tab::make()
                ->label('Todas')
                ->modifyQueryUsing(function ($query) use ($currentFieldId) {
                    // Mostrar todos los registros del field actual
                    return $query->where('field_id', $currentFieldId);
                }),
        ];

        // Crear pestañas para cada bodega del campo actual
        foreach ($wharehouses as $wharehouse) {
            $tabs[$wharehouse->id] = Tab::make()
                ->label($wharehouse->name)
                ->modifyQueryUsing(function ($query) use ($wharehouse, $currentFieldId) {
                    // Filtrar los registros por bodega de origen o destino y field actual
                    return $query->where('field_id', $currentFieldId)
                        ->where(function ($q) use ($wharehouse) {
                            $q->where('bodega_origen_id', $wharehouse->id)
                                ->orWhere('bodega_destino_id', $wharehouse->id);
                        });
                });
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
