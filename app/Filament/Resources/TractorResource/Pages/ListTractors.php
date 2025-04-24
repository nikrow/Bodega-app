<?php

namespace App\Filament\Resources\TractorResource\Pages;

use App\Enums\ProviderType;
use Filament\Actions;
use App\Models\Tractor;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TractorResource;

class ListTractors extends ListRecords
{
    protected static string $resource = TractorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        $tabs = [
            'Todos' => \Filament\Resources\Components\Tab::make()
                ->label('Todos')
                ->modifyQueryUsing(function ($query) {
                    return $query;
                }),
        ];
        $Providers = ProviderType::cases();
        foreach ($Providers as $provider) {
            $tabs[$provider->value] = \Filament\Resources\Components\Tab::make()
                ->label($provider->name)
                ->modifyQueryUsing(function ($query) use ($provider) {
                    return $query->where('provider', $provider->value);
                });
        }
        return $tabs;
    }
}
