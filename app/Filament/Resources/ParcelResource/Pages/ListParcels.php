<?php

namespace App\Filament\Resources\ParcelResource\Pages;

use App\Models\Crop;
use Filament\Actions;
use App\Imports\ParcelImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Importar Cuarteles')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel o CSV')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                        ])
                        ->storeFiles(false),
                ])
                ->action(function (array $data) {
                    $file = $data['file'];

                    if (!($file instanceof \Illuminate\Http\UploadedFile)) {
                        Notification::make()
                            ->title('Error de archivo')
                            ->body('El archivo subido no es válido.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $import = new ParcelImport();

                    DB::beginTransaction();

                    try {
                        Excel::import($import, $file);

                        $processedParcelsList = $import->getProcessedParcels();
                        $fieldIds = collect($processedParcelsList)
                            ->pluck('field_id')
                            ->unique()
                            ->toArray();

                        if (!empty($processedParcelsList) && !empty($fieldIds)) {
                            $import->deactivateMissingParcels($fieldIds);
                        }

                        DB::commit();

                        $summary = $import->getSummary();
                        $rowDetails = $import->getRowDetails();

                        // Generar el archivo CSV
                        $csvPath = $this->generateImportLogCsv($rowDetails);
                        $csvUrl = Storage::url($csvPath);

                        // Construir el cuerpo de la notificación con una tabla
                        $body = "Resumen: Creados: {$summary['created']}, Actualizados: {$summary['updated']}, Desactivados: {$summary['deactivated']}.";
                        
                        Notification::make()
                            ->title('Importación de Cuarteles completada')
                            ->body($body)
                            ->success()
                            ->actions([
                                Action::make('download_log')
                                    ->label('Descargar Log (CSV)')
                                    ->url($csvUrl)
                                    ->openUrlInNewTab()
                                    ->icon('bi-download'),
                            ])
                            ->persistent()
                            ->send();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Error inesperado durante la importación: " . $e->getMessage(), ['exception' => $e]);
                        Notification::make()
                            ->title('Error durante la importación')
                            ->body('Ocurrió un error inesperado: ' . $e->getMessage())
                            ->danger()
                            ->duration(15000)
                            ->send();
                    }
                })
                ->icon('bi-upload'),
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Generar un archivo CSV con el log de la importación.
     */
    protected function generateImportLogCsv(array $rowDetails): string
    {
        $filename = 'import_log_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.csv';
        $path = 'imports/' . $filename;

        $headers = ['Predio', 'Cuartel', 'Cultivo', 'Año', 'Superficie', 'Plantas Productivas', 'Estado', 'Mensaje'];
        $rows = [];

        foreach ($rowDetails as $detail) {
            $rows[] = [
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
                    default => $detail['status'],
                },
                $detail['message'],
            ];
        }

        // Generar el contenido del CSV
        $content = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $content .= implode(',', array_map('strval', $row)) . "\n";
        }

        // Guardar el archivo en el almacenamiento
        Storage::disk('public')->put($path, $content);

        return $path;
    }
}