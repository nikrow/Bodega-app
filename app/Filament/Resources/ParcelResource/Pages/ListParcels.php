<?php

namespace App\Filament\Resources\ParcelResource\Pages;

use App\Models\Crop;
use Filament\Actions;
use App\Imports\ParcelImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ParcelResource;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Notifications\Actions\Action;

class ListParcels extends ListRecords
{
    protected static string $resource = ParcelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /* ExcelImportAction::make()
                ->use(ParcelImport::class)
                ->afterImport(function (array $data, $livewire) {
                    /** @var ParcelImport $import */
                    /* $import = $data['import'] ?? null;

                    if (!$import) {
                        Log::warning('No se pudo obtener la instancia del import.');
                        return;
                    }

                    $processedParcels = $import->getProcessedParcels();
                    $fieldIds = collect($processedParcels)->pluck('field_id')->unique()->toArray();

                    if (!empty($fieldIds)) {
                        $import->deactivateMissingParcels($fieldIds);
                    }

                    // Generar log
                    $csvPath = $this->generateImportLogCsv($import->getProcessedParcels());

                    Notification::make()
                        ->title('Importación completada')
                        ->success()
                        ->body('Se importaron correctamente los cuarteles.')
                        ->actions([
                            Action::make('Ver Log')
                                ->url(Storage::disk('public')->url($csvPath), true)
                                ->label('Descargar reporte'),
                        ])
                        ->send(); */

            Actions\CreateAction::make(),
        ];
    }

    /**
     * Generar un archivo CSV con el log de la importación.
     * Uses fputcsv for proper CSV formatting.
     */
    /* protected function generateImportLogCsv(array $rowDetails): string
    {
        $filename = 'import_log_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.csv';
        $path = 'imports/' . $filename;

        $headers = ['Predio', 'Cuartel', 'Cultivo', 'Año', 'Superficie', 'Plantas Productivas', 'Estado', 'Mensaje'];

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headers);

        foreach ($rowDetails as $detail) {

            $row = [
                $detail['row']['predio'] ?? '-',
                $detail['row']['cuartel'] ?? '-',
                $detail['row']['cultivo'] ?? '-',
                $detail['row']['ano'] ?? '-',
                $detail['row']['superficie'] ?? '-',
                $detail['row']['plantas_productivas'] ?? '-',
                match ($detail['status']) {
                    'created' => 'Creado',
                    'updated' => 'Actualizado',
                    'deactivated' => 'Desactivado',
                    'error' => 'Error',
                    'warning' => 'Advertencia',
                    default => $detail['status'],
                },
                $detail['message'],
            ];
            fputcsv($handle, $row);
        }

        // Rewind the handle to the beginning
        rewind($handle);

        // Read the content
        $content = stream_get_contents($handle);

        // Close the handle
        fclose($handle);

        // Save the content to storage
        Storage::disk('public')->put($path, $content);

        return $path;
    }
 */
}