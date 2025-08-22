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

        // ─────────────────────────────────────────────────────────────
        // Índice en memoria de parcelas: nombre_normalizado => id
        // ─────────────────────────────────────────────────────────────
        $parcelIndex = Parcel::query()
            ->get(['id', 'name'])
            ->reduce(function ($carry, $p) {
                $key = $this->normalizeString($p->name);
                if ($key) $carry[$key] = $p->id;
                return $carry;
            }, []);

        // Mapeos de fertilizantes por columna de Excel
        $mappings = FertilizerMapping::with('product')->get()->keyBy('excel_column_name');

        $totalRecords = 0;
        $successCount = 0;
        $failedCount = 0;

        ImportedEvent::where('tenant', $this->tenant)
            ->where('status', 'pending')
            ->chunk(100, function ($events) use ($mappings, &$totalRecords, &$successCount, &$failedCount, $parcelIndex) {
                DB::transaction(function () use ($events, $mappings, &$totalRecords, &$successCount, &$failedCount, $parcelIndex) {
                    foreach ($events as $event) {
                        $totalRecords++;
                        try {
                            if (!$this->validateEvent($event, $parcelIndex)) {
                                $event->update([
                                    'status' => 'failed',
                                    'error_message' => 'Datos inválidos en el evento.'
                                ]);
                                $failedCount++;
                                continue;
                            }

                            $parcelId = $this->getParcelId($event->description, $parcelIndex);
                            $fieldId  = $this->getFieldId($event->tenant);

                            $irrigation = Irrigation::create([
                                'parcel_id'   => $parcelId,
                                'field_id'    => $fieldId,
                                'date'        => $event->date_time->format('Y-m-d'),
                                'time'        => $event->date_time->format('H:i:s'),
                                'duration'    => $event->duration,
                                'quantity_m3' => $event->quantity_m3,
                                'type'        => 'Riego',
                            ]);

                            if (!empty($event->fertilizers)) {
                                $parcel  = Parcel::find($irrigation->parcel_id);
                                $surface = $parcel ? $parcel->surface : 0;

                                foreach ($event->fertilizers as $column => $quantity_solution) {
                                    $mapping = $mappings->get($column);
                                    if ($mapping) {
                                        $dilution_factor = $mapping->dilution_factor;
                                        $quantity_product = $quantity_solution * $dilution_factor;

                                        $product = $mapping->product;
                                        $product_price = $product ? $product->price : null;
                                        $total_cost = $product_price ? $quantity_product * $product_price : null;

                                        Fertilization::create([
                                            'irrigation_id'          => $irrigation->id,
                                            'parcel_id'              => $irrigation->parcel_id,
                                            'field_id'               => $irrigation->field_id,
                                            'date'                   => $event->date_time->format('Y-m-d'),
                                            'product_id'             => $mapping->product_id,
                                            'fertilizer_mapping_id'  => $mapping->id,
                                            'surface'                => $surface,
                                            'quantity_solution'      => $quantity_solution,
                                            'dilution_factor'        => $dilution_factor,
                                            'quantity_product'       => $quantity_product,
                                            'unit'                   => $mapping->unit,
                                            'product_price'          => $product_price,
                                            'total_cost'             => $total_cost,
                                            'application_method'     => 'ICC',
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
            'failed_count'  => $failedCount,
        ]);
    }

    /**
     * Normaliza una cadena para comparaciones consistentes:
     * - Asegura UTF-8
     * - Reemplaza guiones largos por '-'
     * - Reemplaza NBSP (U+00A0) y su forma UTF-8 (\xC2\xA0) por espacio
     * - Colapsa espacios, trim y lowercase
     */
    private function normalizeString(?string $value): ?string
    {
        if ($value === null) return null;

        // Forzar UTF-8 si viene en otra codificación
        if (!mb_detect_encoding($value, 'UTF-8', true)) {
            $value = mb_convert_encoding($value, 'UTF-8');
        }

        // Guiones tipográficos → '-'
        $value = str_replace(['–', '—'], '-', $value);

        // NBSP en dos formas comunes
        $value = str_replace("\xC2\xA0", ' ', $value);           // UTF-8 NBSP
        $value = preg_replace('/\x{00A0}/u', ' ', $value);       // Unicode NBSP

        // Colapsar espacios múltiples y limpieza
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        // Lowercase seguro
        return mb_strtolower($value);
    }

    /**
     * Valida un ImportedEvent con índice de parcelas normalizado.
     */
    protected function validateEvent(ImportedEvent $event, array $parcelIndex): bool
    {
        // Validar parcel_id (por nombre normalizado via índice)
        $parcelId = $this->getParcelId($event->description, $parcelIndex);
        if (!$parcelId) {
            return false;
        }

        // Validar field_id
        $fieldId = $this->getFieldId($event->tenant);
        if (!$fieldId) {
            return false;
        }

        // Validar duración
        if ($event->duration < 0) {
            return false;
        }

        // Validar fertilizantes (columnas conocidas)
        if (!empty($event->fertilizers)) {
            $validCols = FertilizerMapping::pluck('excel_column_name')->toArray();
            foreach ($event->fertilizers as $column => $quantity) {
                if (!in_array($column, $validCols, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Obtiene el ID de la parcela desde la descripción usando el índice normalizado.
     */
    protected function getParcelId($description, array $parcelIndex = null)
    {
        $needle = $this->normalizeString($description);
        if (!$needle) {
            Log::error('Parcela no encontrada (descripción vacía/nula).', [
                'raw_description' => $description,
            ]);
            return null;
        }

        if (is_array($parcelIndex)) {
            if (array_key_exists($needle, $parcelIndex)) {
                return $parcelIndex[$needle];
            }

            // Fallback simple por si hay leves diferencias de espacios:
            $first = explode(' ', $needle)[0] ?? null;
            if ($first) {
                foreach ($parcelIndex as $normName => $id) {
                    if ($normName === $needle) {
                        return $id;
                    }
                }
            }

            Log::error('Parcela no encontrada (índice en memoria).', [
                'raw_description' => $description,
                'normalized'      => $needle,
            ]);
            return null;
        }

        // Fallback DB (poco común): traer algunos candidatos y comparar normalizado en PHP
        $candidates = Parcel::where('name', 'like', '%' . ($description ?? '') . '%')
            ->limit(50)
            ->get(['id','name']);

        foreach ($candidates as $c) {
            if ($this->normalizeString($c->name) === $needle) {
                return $c->id;
            }
        }

        Log::error('Parcela no encontrada (fallback DB).', [
            'raw_description' => $description,
            'normalized'      => $needle,
        ]);
        return null;
    }

    /**
     * Obtiene el field_id; si el tenant es el id del Field, usa find().
     * Ajusta esto si tu relación tenant→field se resuelve distinto.
     */
    protected function getFieldId($tenant)
    {
        // Antes: Field::where('id', $this->tenant)->first();
        $field = Field::find($this->tenant);
        if (!$field) {
            Log::error('Field no encontrado para tenant.', ['tenant' => $this->tenant]);
            return null;
        }
        return $field->id;
    }
}
