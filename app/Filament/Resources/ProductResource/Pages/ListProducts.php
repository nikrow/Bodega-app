<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\FamilyType;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $families = FamilyType::cases();
        $tabs = [
            'Todos' => Tab::make()
                ->label('Todos'),
        ];

        foreach ($families as $family) {
            $tabs[$family->value] = Tab::make()
                ->label($family->name)
                ->query(function (Builder $query) use ($family) {
                    return $query->where('family', $family);
                });
        }
        return $tabs;
    }

}
