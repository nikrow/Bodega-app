<?php

namespace App\Filament\Resources\ConsolidatedReportResource\Pages;

use Filament\Actions;
use App\Enums\ProviderType;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ConsolidatedReportResource;

class ListConsolidatedReports extends ListRecords
{
    protected static string $resource = ConsolidatedReportResource::class;

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

        $providers = ProviderType::cases();
        foreach ($providers as $provider) {
            $tabs[$provider->value] = \Filament\Resources\Components\Tab::make()
                ->label($provider->name)
                ->modifyQueryUsing(function ($query) use ($provider) {
                    return $query->where(function ($subQuery) use ($provider) {
                        $subQuery
                            ->whereHas('machinery', function ($q) use ($provider) {
                                $q->where('provider', $provider->value);
                            })
                            ->orWhereHas('tractor', function ($q) use ($provider) {
                                $q->where('provider', $provider->value);
                            });
                    });
                });
        }

        return $tabs;
    }
}