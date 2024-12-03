<?php

namespace App\Filament\Resources\ParcelResource\Pages;

use App\Filament\Resources\ParcelResource;
use App\Models\Crop;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListParcels extends ListRecords
{
    protected static string $resource = ParcelResource::class;

    /**
     * Retrieve the tabs based on Crop models.
     *
     * @return array
     */
    public function getTabs(): array
    {

        $crops = Crop::all();

        $tabs = [
            'Todos' => Tab::make()
                ->label('Todos')
                ->query(function (Builder $query) {
                    return $query;
                }),

        ];

        if ($crops->isEmpty()) {

            $tabs['no_crops'] = Tab::make()
                ->label('Sin cultivo disponible')
                ->query(function (Builder $query) {

                    return $query;
                });
        } else {
            foreach ($crops as $crop) {
                $tabs[$crop->id] = Tab::make()
                    ->label($crop->especie)
                    ->query(function (Builder $query) use ($crop) {
                        return $query->where('crop_id', $crop->id);
                    });
            }
        }

        return $tabs;
    }

    /**
     * Define the header actions.
     *
     * @return array
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
