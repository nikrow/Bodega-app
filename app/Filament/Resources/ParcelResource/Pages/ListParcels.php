<?php

namespace App\Filament\Resources\ParcelResource\Pages;

use App\Filament\Resources\ParcelResource;
use Filament\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListParcels extends ListRecords
{
    protected static string $resource = ParcelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->slideOver()
                ->sampleExcel(
                    sampleData: [
                        ['name' => 'Parcela 1', 'field_id'=> 1, 'crop_id'=> 1,'planting_year' => 2024, 'plants' => 100, 'surface' => 10.2],
                        ['name' => 'Parcela 2', 'field_id'=> 1, 'crop_id'=> 1,'planting_year' => 2024, 'plants' => 100, 'surface' => 1.3],
                        ['name' => 'Parcela 3', 'field_id'=> 1, 'crop_id'=> 1,'planting_year' => 2024, 'plants' => 100, 'surface' => 3.3],
                    ],
                    fileName: 'MuestraCuarteles.xlsx',
                    sampleButtonLabel: 'Descargar muestra',
                    customiseActionUsing: fn(Action $action) => $action->color('secondary')
                        ->icon('heroicon-m-clipboard')
                        ->requiresConfirmation(),
                )
                ->label('Importar cuarteles')
                ->validateUsing([
                    'name' => ['required', 'string', 'max:255'],
                    'field_id' => ['required', 'integer'],
                    'crop_id' => ['required', 'integer'],
                    'planting_year' => ['required', 'integer'],
                    'plants' => ['required', 'integer'],
                    'surface' => ['required'],
                ])
                ->color("primary"),
            Actions\CreateAction::make(),
        ];
    }
}
