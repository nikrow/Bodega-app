<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

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



    /**
     * Método opcional para definir si el redireccionamiento es necesario o cualquier lógica extra.
     */
    protected function afterCreate(): void
    {
        CreateAction::make()
            ->successRedirectUrl(fn(Model $record): string => route('orders.edit', $record->id));
    }
}
