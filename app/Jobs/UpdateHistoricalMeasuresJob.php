<?php

namespace App\Jobs;

use App\Models\Zone;
use App\Models\Field;
use App\Services\WiseconnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateHistoricalMeasuresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $zone;
    protected $field;

    public function __construct(Zone $zone, Field $field)
    {
        $this->zone = $zone;
        $this->field = $field;
    }

    public function handle(WiseconnService $wiseconnService): void
    {
        Log::info("Iniciando UpdateHistoricalMeasuresJob para la zona: {$this->zone->name} (ID: {$this->zone->id}) del Field: {$this->field->name} (ID: {$this->field->id}).");

        try {
            $wiseconnService->updateHistoricalMeasures($this->field, $this->zone);
            Log::info("UpdateHistoricalMeasuresJob completado exitosamente para la zona {$this->zone->name}.");
        } catch (\Exception $e) {
            Log::error("Error en UpdateHistoricalMeasuresJob para la zona {$this->zone->name}: {$e->getMessage()}");
            throw $e; 
        }
    }
}