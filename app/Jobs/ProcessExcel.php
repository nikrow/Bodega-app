<?php
namespace App\Jobs;

use App\Models\Field;
use App\Models\Parcel;
use App\Models\Irrigation;
use App\Models\ImportBatch;
use App\Models\Fertilization;
use App\Models\ImportedEvent;
use Illuminate\Bus\Queueable;
use App\Models\FertilizerMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Imports\IrrigationFertilizationImport;

class ProcessExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $tenant;

    public function __construct($path, $tenant)
    {
        $this->path = $path;
        $this->tenant = $tenant;
    }

    public function handle()
    {
        $fullPath = storage_path('app/' . $this->path);
        if (!file_exists($fullPath)) {
            Log::error('El archivo no existe', ['path' => $fullPath]);
            return;
        }

        $batch = ImportBatch::create([
        'tenant' => $this->tenant,
        'import_date' => now(),
        'total_records' => 0,
        'success_count' => 0,
        'failed_count' => 0,
        ]);

        Log::info('Procesando archivo', ['path' => $fullPath]);
        Excel::import(new IrrigationFertilizationImport($this->tenant, $batch->id), $fullPath);
        Log::info('Datos importados correctamente', ['path' => $this->path]);

        $mappings = FertilizerMapping::with('product')->get()->keyBy('excel_column_name');

        $totalRecords = 0;
        $successCount = 0;
        $failedCount = 0;
        ImportedEvent::where('tenant', $this->tenant)
            ->where('status', 'pending')
            ->chunk(100, function ($events) use ($mappings, &$totalRecords, &$successCount, &$failedCount) {
            DB::transaction(function () use ($events, $mappings, &$totalRecords, &$successCount, &$failedCount) {
                foreach ($events as $event) {
                    $totalRecords++;
                    try {
                        if (!$this->validateEvent($event)) {
                            $event->update([
                                'status' => 'failed',
                                'error_message' => 'Datos inválidos en el evento.'
                            ]);
                            $failedCount++;
                            continue;
                        }

                        $irrigation = Irrigation::create([
                            'parcel_id' => $this->getParcelId($event->description),
                            'field_id' => $this->getFieldId($event->tenant),
                            'date' => $event->date_time->format('Y-m-d'),
                            'time' => $event->date_time->format('H:i:s'),
                            'duration' => $event->duration,
                            'quantity_m3' => $event->quantity_m3,
                            'type' => 'Riego',
                        ]);

                        if (!empty($event->fertilizers)) {
                            $parcel = Parcel::find($irrigation->parcel_id);
                            $surface = $parcel ? $parcel->surface : 0;

                            foreach ($event->fertilizers as $column => $quantity_solution) {
                                $mapping = $mappings->get($column);
                                if ($mapping) {
                                    $dilution_factor = $mapping->dilution_factor;
                                    $quantity_product = $quantity_solution * $dilution_factor;

                                    Fertilization::create([
                                        'irrigation_id' => $irrigation->id,
                                        'parcel_id' => $irrigation->parcel_id,
                                        'field_id' => $irrigation->field_id,
                                        'date' => $event->date_time->format('Y-m-d'),
                                        'product_id' => $mapping->product_id,
                                        'surface' => $surface,
                                        'quantity_solution' => $quantity_solution,
                                        'dilution_factor' => $dilution_factor,
                                        'quantity_product' => $quantity_product,
                                        'unit' => $mapping->unit,
                                    ]);
                                } else {
                                    Log::warning("Columna '$column' no mapeada.", ['event_id' => $event->id]);
                                }
                            }
                        }

                        $event->update(['status' => 'processed']);
                        $successCount++;
                    } catch (\Exception $e) {
                        $event->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                        Log::error('Error procesando evento.', ['event_id' => $event->id, 'error' => $e->getMessage()]);
                        $failedCount++;
                    }
                }
            });
        });

    $batch->update([
        'total_records' => $totalRecords,
        'success_count' => $successCount,
        'failed_count' => $failedCount,
    ]);
    }

    protected function validateEvent(ImportedEvent $event): bool
    {
        // Validar parcel_id
        $parcelId = $this->getParcelId($event->description);
        if (!$parcelId) {
            return false;
        }

        // Validar field_id
        $fieldId = $this->getFieldId($event->tenant);
        if (!$fieldId) {
            return false;
        }

        // Validar duración

        if ($event->duration < 0 ) {
        return false;
    }

        // Validar fertilizantes
        if (!empty($event->fertilizers)) {
            $mappings = FertilizerMapping::pluck('excel_column_name')->toArray();
            foreach ($event->fertilizers as $column => $quantity) {
                if (!in_array($column, $mappings)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function getParcelId($description)
    {
        $parcel = Parcel::where('name', $description)->first();
        if (!$parcel) {
            Log::error('Parcela no encontrada para descripción.', ['description' => $description]);
            return null;
        }
        return $parcel->id;
    }

    protected function getFieldId($tenant)
    {
    $field = Field::where('id', $tenant)->first();
    if (!$field) {
        Log::error('Field no encontrado para tenant.', ['tenant' => $tenant]);
        return null;
    }
    return $field->id;
    }
}