<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Models\Parcel;
use App\Models\Field;
use App\Models\Crop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ParcelImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $processedParcels = [];
    protected $createdCount = 0;
    protected $updatedCount = 0;
    protected $deactivatedCount = 0;
    protected $rowDetails = []; 

    /**
     * Procesar cada fila del archivo subido.
     */
    public function model(array $row)
    {
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
                    'plants' => $row['plantas_productivas'] ?? 0,
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
                    'plants' => $row['plantas_productivas'] ?? 0,
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
            $this->processedParcels[] = ['name' => $row['cuartel'], 'field_id' => $field->id];

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
    public function deactivateMissingParcels(array $fieldIds)
    {
        $existingParcels = Parcel::whereIn('field_id', $fieldIds)
            ->where('is_active', true)
            ->get();

        foreach ($existingParcels as $parcel) {
            $isProcessed = collect($this->processedParcels)->contains(function ($processed) use ($parcel) {
                return $processed['name'] === $parcel->name && $processed['field_id'] === $parcel->field_id;
            });

            if (!$isProcessed) {
                $parcel->update([
                    'is_active' => false,
                    'deactivated_at' => now(),
                    'deactivated_by' => Auth::id(),
                    'deactivation_reason' => 'Desactivado durante la importación',
                ]);
                $this->rowDetails[] = [
                    'row' => ['cuartel' => $parcel->name, 'predio' => $parcel->field->name],
                    'status' => 'deactivated',
                    'message' => "Cuartel desactivado: {$parcel->name}",
                ];
                Log::info("Cuartel desactivado: {$parcel->name}");
                $this->deactivatedCount++;
            }
        }
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
            'plantas_productivas' => 'required|integer',
        ];
    }
}