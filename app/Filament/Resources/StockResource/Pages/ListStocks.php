<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Models\Warehouse;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Enums\FiltersLayout;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use Filament\Facades\Filament;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;


    public function getTabs(): array
    {
        // Obtener el campo actual (field) del usuario
        $currentFieldId = Filament::getTenant()->id;

        // Obtener todas las bodegas que pertenecen al campo actual
        $warehouses = Warehouse::where('field_id', $currentFieldId)->get();

        // Crear una pestaña para todas las bodegas que pertenecen al campo actual
        $tabs = [
            'Todas' => ListRecords\Tab::make()
                ->label('Todas')
                ->modifyQueryUsing(function ($query) use ($currentFieldId) {
                    // Mostrar todos los registros del field actual
                    return $query->where('field_id', $currentFieldId);
                }),
        ];

        // Crear pestañas para cada bodega del campo actual
        foreach ($warehouses as $warehouse) {
            $tabs[$warehouse->id] = ListRecords\Tab::make()
                ->label($warehouse->name)
                ->modifyQueryUsing(function ($query) use ($warehouse, $currentFieldId) {
                    // Filtrar los registros por bodega y field actual
                    return $query->where('warehouse_id', $warehouse->id)
                        ->where('field_id', $currentFieldId);
                });
        }

        return $tabs;
    }
}
