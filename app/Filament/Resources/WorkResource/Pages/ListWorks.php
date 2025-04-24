<?php

namespace App\Filament\Resources\WorkResource\Pages;

use App\Models\Work;
use Filament\Actions;
use App\Enums\CostType;
use App\Filament\Resources\WorkResource;
use Filament\Resources\Pages\ListRecords;

class ListWorks extends ListRecords
{
    protected static string $resource = WorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        $tabs = [
            'all' => \Filament\Resources\Components\Tab::make()
                ->label('Todos')
                ->modifyQueryUsing(fn ($query) => $query),
        ];

        
        $costTypes = CostType::cases();
        
    foreach ($costTypes as $costType) {
        $tabs[$costType->value] = \Filament\Resources\Components\Tab::make()
            
            ->modifyQueryUsing(fn ($query) => $query->where('cost_type', $costType->value));
    }

        return $tabs;
    }
}
