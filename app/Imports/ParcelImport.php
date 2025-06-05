<?php

namespace App\Imports;

use App\Models\Crop;
use App\Models\Field;
use App\Models\Parcel;
use Illuminate\Support\Facades\Log;
use App\Events\ImportCompletedEvent;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ParcelImport implements ToModel, WithHeadingRow, WithChunkReading
{
    protected $processedParcels = [];
    protected $createdCount = 0;
    protected $updatedCount = 0;
    protected $deactivatedCount = 0;
    protected $rowDetails = [];

    public static function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'handleAfterImport'],
        ];
    }

    public static function handleAfterImport(AfterImport $event)
    {
        $import = $event->getConcernable();
        $processedParcelsList = $import->getProcessedParcels();
        $fieldIds = collect($processedParcelsList)->pluck('field_id')->unique()->toArray();

        if (!empty($processedParcelsList) && !empty($fieldIds)) {
            $import->deactivateMissingParcels($fieldIds);
        }

        $summary = $import->getSummary();
        $rowDetails = $import->getRowDetails();
        event(new ImportCompletedEvent($summary, $rowDetails));
    }
    /**
     * Procesar cada fila del archivo subido.
     */
    public function model(array $row)
    {
        // Validar la fila manualmente
        $validator = Validator::make($row, $this->rules());

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $this->rowDetails[] = [
                'row' => $row,
                'status' => 'error',
                'message' => "Errores de validación: " . implode(', ', $errors),
            ];
            Log::warning("Errores de validación en la fila: " . json_encode($row) . " - " . implode(', ', $errors));
            return null; // Continuar con la siguiente fila
        }

        // Procesar plantas_productivas
        $plantas = $row['plantas_productivas'];
        if (is_numeric($plantas)) {
            $row['plantas_productivas'] = (int) round($plantas);
            if ($plantas != $row['plantas_productivas']) {
                $this->rowDetails[] = [
                    'row' => $row,
                    'status' => 'warning',
                    'message' => "Plantas productivas redondeadas de {$plantas} a {$row['plantas_productivas']}",
                ];
            }
        } else {
            $this->rowDetails[] = [
                'row' => $row,
                'status' => 'error',
                'message' => "Plantas productivas no es un número: {$plantas}",
            ];
            Log::warning("Plantas productivas no es un número en la fila: " . json_encode($row));
            return null; // Continuar con la siguiente fila
        }

        try {
            // Buscar el predio (Field)
            $field = Field::where('name', $row['predio'])->first();
            if (!$field) {
                $this->rowDetails[] = [
                    'row' => $row,
                    'status' => 'error',
                    'message' => "Campo no encontrado para Predio: {$row['predio']}",
                ];
                Log::warning("Campo no encontrado para Predio: {$row['predio']}");
                return null;
            }

            // Buscar el cultivo (Crop)
            $crop = Crop::where('especie', $row['cultivo'])->first();
            if (!$crop) {
                $this->rowDetails[] = [
                    'row' => $row,
                    'status' => 'error',
                    'message' => "Cultivo no encontrado para Cultivo: {$row['cultivo']}",
                ];
                Log::warning("Cultivo no encontrado para Cultivo: {$row['cultivo']}");
                return null;
            }

            // Buscar o crear la parcela
            $parcel = Parcel::where('name', $row['cuartel'])
                ->where('field_id', $field->id)
                ->first();

            if ($parcel) {
                // Actualizar parcela existente
                $parcel->update([
                    'crop_id' => $crop->id,
                    'planting_year' => $row['ano'],
                    'surface' => $row['superficie'] ?? null,
                    'plants' => $row['plantas_productivas'],
                    'updated_by' => Auth::id(),
                ]);
                $this->rowDetails[] = [
                    'row' => $row,
                    'status' => 'updated',
                    'message' => "Cuartel actualizado: {$parcel->name}",
                ];
                Log::info("Cuartel actualizado: {$parcel->name}");
                $this->updatedCount++;
            } else {
                // Crear nueva parcela
                $parcel = Parcel::create([
                    'name' => $row['cuartel'],
                    'field_id' => $field->id,
                    'crop_id' => $crop->id,
                    'planting_year' => $row['ano'],
                    'surface' => $row['superficie'] ?? null,
                    'plants' => $row['plantas_productivas'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'is_active' => true,
                ]);
                $this->rowDetails[] = [
                    'row' => $row,
                    'status' => 'created',
                    'message' => "Cuartel creado: {$parcel->name}",
                ];
                Log::info("Cuartel creado: {$parcel->name}");
                $this->createdCount++;
            }

            // Registrar la parcela procesada
            $this->processedParcels[] = [
                'name' => $row['cuartel'],
                'field_id' => $field->id,
                'parcel_id' => $parcel->id,
            ];
            return $parcel;
            
        } catch (\Exception $e) {
            $this->rowDetails[] = [
                'row' => $row,
                'status' => 'error',
                'message' => "Error al procesar fila: {$e->getMessage()}",
            ];
            Log::error("Error al procesar fila: " . json_encode($row) . " - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Desactivar parcelas que no están en el archivo subido.
     */
    public function deactivateMissingParcels(array $fieldIds): array
    {
        $processedParcelIds = collect($this->processedParcels)->pluck('parcel_id')->toArray();

        $parcelsToDeactivate = \App\Models\Parcel::query()
            ->whereIn('field_id', $fieldIds)
            ->whereNotIn('id', $processedParcelIds)
            ->get();

        foreach ($parcelsToDeactivate as $parcel) {
            $this->deactivatedCount++;
            $parcel->update(['activo' => false]);

            // Agregar al log
            $this->processedParcels[] = [
                'field_id' => $parcel->field_id,
                'parcel_id' => $parcel->id,
                'status' => 'deactivated',
                'row' => [
                    'predio' => $parcel->field->name ?? '',
                    'cuartel' => $parcel->name,
                    'cultivo' => $parcel->crop->especie ?? '',
                    'ano' => $parcel->year,
                    'superficie' => $parcel->superficie,
                    'plantas_productivas' => $parcel->plantas_productivas,
                ],
                'message' => 'Cuartel desactivado por ausencia en el archivo.',
            ];
        }

        return $parcelsToDeactivate->toArray();
    }


    /**
     * Obtener el resumen de la importación.
     */
    public function getSummary(): array
    {
        return [
            'created' => $this->createdCount,
            'updated' => $this->updatedCount,
            'deactivated' => $this->deactivatedCount,
        ];
    }

    /**
     * Obtener las parcelas procesadas.
     */
    public function getProcessedParcels(): array
    {
        return $this->processedParcels;
    }

    /**
     * Obtener los detalles de las filas procesadas.
     */
    public function getRowDetails(): array
    {
        return $this->rowDetails;
    }

    /**
     * Reglas de validación para las columnas del archivo.
     */
    public function rules(): array
    {
        return [
            'predio' => 'required',
            'cultivo' => 'required',
            'cuartel' => 'required',
            'ano' => 'nullable|integer',
            'superficie' => 'nullable|numeric',
            'plantas_productivas' => 'required|numeric', 
        ];
    }

    /**
     * Definir el tamaño del chunk para procesar el archivo.
     */
    public function chunkSize(): int
    {
        return 100; 
    }
}