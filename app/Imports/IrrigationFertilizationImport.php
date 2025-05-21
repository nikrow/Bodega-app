<?php

namespace App\Imports;

use Carbon\CarbonInterval;
use App\Models\ImportedEvent;
use App\Models\FertilizerMapping;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class IrrigationFertilizationImport implements ToModel, WithHeadingRow
{
    protected $tenant;
    protected $batchId;

    public function __construct($tenant, $batchId)
    {
        $this->tenant = $tenant;
        $this->batchId = $batchId;
    }

    public function model(array $row)
    {

        // Log the raw row data for debugging
        Log::info('Fila leída:', $row);
        
        if (is_null($row['descripcion'])) {
        Log::info('Fila omitida: descripción nula.', $row);
        return null; // Skip this row
    }
        // Convert Excel serial date if dia_hora is numeric
        if (isset($row['dia_hora']) && is_numeric($row['dia_hora'])) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject($row['dia_hora']);
                $row['dia_hora'] = \Carbon\Carbon::instance($dateTime);
            } catch (\Exception $e) {
                Log::error('Error al convertir dia_hora:', ['error' => $e->getMessage(), 'row' => $row]);
                return null;
            }
        } else if (isset($row['dia_hora'])) {
            
            try {
                $row['dia_hora'] = \Carbon\Carbon::createFromFormat('d/m/Y', $row['dia_hora']);
            } catch (\Exception $e) {
                Log::error('Error al parsear dia_hora:', ['error' => $e->getMessage(), 'row' => $row]);
                return null;
            }
        }

        // Convert duration to seconds (integer)
        $durationSeconds = 0;
        if (isset($row['tiempo_de_duracion'])) {
            if (is_numeric($row['tiempo_de_duracion'])) {
                // Assume numeric value is a fraction of a day
                $durationSeconds = (int) ($row['tiempo_de_duracion'] * 86400); // 86400 seconds per day
            } elseif (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $row['tiempo_de_duracion'], $matches)) {
                // Convert HH:MM:SS to seconds
                $hours = (int) $matches[1];
                $minutes = (int) $matches[2];
                $seconds = (int) $matches[3];
                $durationSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        }
        // Validar campos requeridos
        if (empty($row['descripcion']) || empty($row['dia_hora'])) {
            Log::warning('Fila inválida: descripción o fecha/hora faltantes.', $row);
            return null;
        }

        $fertilizerColumns = FertilizerMapping::pluck('excel_column_name')->toArray();
        $fertilizers = [];
        foreach ($fertilizerColumns as $column) {
            if (isset($row[$column]) && (float) $row[$column] > 0) {
                $fertilizers[$column] = (float) $row[$column];
            }
        }
        if ($durationSeconds === 0 && empty($fertilizers)) {
            Log::info('Fila omitida: duración 0 y sin fertilizantes.', $row);
            return null;
        }

        return new ImportedEvent([
            'tenant' => $this->tenant,
            'batch_id' => $this->batchId,
            'description' => $row['descripcion'],
            'date_time' => $row['dia_hora'],
            'duration' => $durationSeconds,
            'quantity_m3' => $row['cantidad_m3'] ?? 0,
            'fertilizers' => $fertilizers,
            'status' => 'pending',
        ]);
    }
}