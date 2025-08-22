<?php

namespace App\Filament\Resources\WorkLogResource\Pages;

use App\Filament\Resources\WorkLogResource;
use App\Models\WorkLog;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateWorkLog extends CreateRecord
{
    protected static string $resource = WorkLogResource::class;

    /**
     * En vez de crear 1 registro, creamos N (uno por ítem del repeater).
     * Retornamos uno cualquiera solo para que Filament quede conforme.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $tenantFieldId = Filament::getTenant()->id;

        $header = [
            'date'           => $data['date'],
            'crop_id'        => $data['crop_id'],
            'responsible_id' => $data['responsible_id'],
            'contractor_id'  => $data['contractor_id'] ?? null,
            'field_id'       => $tenantFieldId, // de todas formas tu modelo lo setea en creating()
        ];

        $created = null;

        DB::transaction(function () use (&$created, $header, $data) {
            foreach (($data['workLogs'] ?? []) as $row) {
                // Tomamos lo necesario del item
                $payload = [
                    'parcel_id'    => $row['parcel_id'] ?? null,
                    'task_id'      => $row['task_id'] ?? null,
                    'people_count' => $row['people_count'] ?? null,
                    'quantity'     => $row['quantity'] ?? null,
                    'unit_type'    => $row['unit_type'] ?? null,
                    'notes'        => $row['notes'] ?? null,
                ];

                // Merge con encabezado y crear
                $created = WorkLog::create(array_merge($header, $payload));
            }
        });

        // Noti amigable
        Notification::make()
            ->title('Faenas registradas')
            ->body('Se crearon ' . count($data['workLogs'] ?? []) . ' registros de faena.')
            ->success()
            ->send();

        // devolver el último creado (o alguno) por contrato del CreateRecord
        return $created ?? WorkLog::query()->latest('id')->first();
    }

    /**
     * Evitar que Filament intente redirigir al "edit" de un único registro,
     * mejor volvemos al índice.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
