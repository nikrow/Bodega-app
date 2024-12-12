<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['field_id'])) {
            // Si no hay `field_id`, podría fallar la creación, así que devuelve algún valor predeterminado o lanza un error.
            $data['field_id'] = Filament::getTenant()->id ?? throw new \Exception('Field ID is required');
        }

        $data['orderNumber'] = Order::generateUniqueOrderNumber($data['field_id']);
        return $data;
    }

    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }



}
