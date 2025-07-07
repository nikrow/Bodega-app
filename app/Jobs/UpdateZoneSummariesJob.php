<?php

namespace App\Jobs;

use App\Models\Field;
use App\Models\Zone; // Import the Zone model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateZoneSummariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $field; // This job now only receives a Field object

    /**
     * Create a new job instance.
     *
     * @param Field $field The field (farm) for which zones need to be summarized.
     * @return void
     */
    public function __construct(Field $field)
    {
        Log::info("Construyendo UpdateZoneSummariesJob con campo ID: " . ($field ? $field->id : 'null'));
        $this->field = $field;
    }

    /**
     * Execute the job.
     * This method fetches all zones for the given field and dispatches individual jobs for each zone.
     * @return void
     */
    public function handle(): void
    {
        if (is_null($this->field)) {
            Log::error("El campo es null en UpdateZoneSummariesJob, no se puede procesar.");
            return;
        }

        Log::info("Iniciando procesamiento de Zone Summaries para todas las zonas del campo: {$this->field->name} (ID: {$this->field->id}).");

        // Retrieve all zones associated with the current field.
        // This ensures we process all zones belonging to this specific field.
        $zones = Zone::where('field_id', $this->field->id)->get();

        if ($zones->isEmpty()) {
            Log::info("No se encontraron zonas para el campo: {$this->field->name} (ID: {$this->field->id}).");
            return;
        }

        foreach ($zones as $zone) {
            // Dispatch a job for each individual zone.
            // This allows for parallel processing of zones if multiple queue workers are running,
            // and isolates potential failures to a single zone.
            Log::info("Despachando UpdateSingleZoneSummaryJob para la zona: {$zone->name} (ID: {$zone->id}) en el campo: {$this->field->name}.");
            UpdateSingleZoneSummaryJob::dispatch($this->field, $zone)
                ->onQueue('zone_summaries'); // Using a dedicated queue for zone summaries
        }

        Log::info("Finalizada la actualización de Zone Summaries para el campo: {$this->field->name} (ID: {$this->field->id}).");
    }
}