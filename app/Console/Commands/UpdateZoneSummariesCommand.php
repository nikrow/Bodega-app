<?php

namespace App\Console\Commands;

use App\Models\Field; // Import the Field model
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateZoneSummariesJob;

class UpdateZoneSummariesCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'zones:update-summaries';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Actualiza los resúmenes de medidas actuales y diarias de todas las zonas desde la API de Wiseconn.';

    /**
     * Execute the console command.
     * This command dispatches a job for each field to process its associated zones.
     * @return void
     */
    public function handle(): void
    {
        Log::info('Iniciando el comando UpdateZoneSummariesCommand.');

        $fields = Field::all();

        if ($fields->isEmpty()) {
            $this->info('No se encontraron campos para actualizar.');
            Log::info('No se encontraron campos para actualizar.');
            return;
        }

        foreach ($fields as $field) {
            // Dispatch the UpdateZoneSummariesJob for each field.
            // This job will then find and process all zones belonging to this field.
            // Using a queue ('summaries') ensures the process runs in the background.
            Log::info("Despachando UpdateZoneSummariesJob para el campo: {$field->name} (ID: {$field->id}).");
            UpdateZoneSummariesJob::dispatch($field)->onQueue('summaries');
        }

        $this->info('Jobs para actualizar resúmenes de zonas despachados exitosamente por campo.');
        Log::info('Comando UpdateZoneSummariesCommand finalizado.');
    }
}