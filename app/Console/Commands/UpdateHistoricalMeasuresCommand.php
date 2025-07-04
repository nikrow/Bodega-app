<?php

namespace App\Console\Commands;

use App\Models\Zone;
use App\Jobs\UpdateHistoricalMeasuresJob;
use Illuminate\Console\Command;

class UpdateHistoricalMeasuresCommand extends Command
{
    protected $signature = 'zones:update-historical-measures';
    protected $description = 'Actualiza el historial de medidas de todas las zonas desde la API de Wiseconn';

    public function handle(): void
    {
        $zones = Zone::with('field')->whereJsonContains('type', 'Weather')->get();

        foreach ($zones as $zone) {
            if ($zone->field) {
                UpdateHistoricalMeasuresJob::dispatch($zone, $zone->field)->onQueue('measures');
            }
        }

        $this->info('Jobs para actualizar historial de medidas despachados exitosamente.');
    }
}