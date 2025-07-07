<?php

namespace App\Jobs;

use App\Models\Field;
use App\Models\Zone;
use App\Services\WiseconnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateSingleZoneSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $field;
    protected $zone;

    /**
     * Create a new job instance.
     *
     * @param Field $field The field model instance.
     * @param Zone $zone The zone model instance.
     * @return void
     */
    public function __construct(Field $field, Zone $zone)
    {
        $this->field = $field;
        $this->zone = $zone;
        Log::info("Construyendo UpdateSingleZoneSummaryJob para Zona ID: {$zone->id} y Campo ID: {$field->id}");
    }

    /**
     * Execute the job.
     * This method calls the WiseconnService to update the summary for a single zone.
     * @param WiseconnService $wiseconnService The WiseconnService instance, resolved via dependency injection.
     * @return void
     */
    public function handle(WiseconnService $wiseconnService): void
    {
        Log::info("Manejando UpdateSingleZoneSummaryJob para Zona ID: {$this->zone->id} y Campo ID: {$this->field->id}");
        try {
            // Call the updateZoneSummary method on the WiseconnService, passing the field and zone
            $wiseconnService->updateZoneSummary($this->field, $this->zone);
            Log::info("Finalizado UpdateSingleZoneSummaryJob para Zona ID: {$this->zone->id} exitosamente.");
        } catch (\Exception $e) {
            Log::error("Error en UpdateSingleZoneSummaryJob para Zona ID: {$this->zone->id}: {$e->getMessage()}");
            // You might want to re-throw the exception or handle it more specifically
            // depending on your error handling strategy (e.g., failed jobs table).
        }
    }
}