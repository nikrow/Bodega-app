<?php

namespace App\Console\Commands;

use App\Models\Zone;
use App\Jobs\UpdateZoneSummariesJob;
use Illuminate\Console\Command;

class UpdateZoneSummariesCommand extends Command
{
    protected $signature = 'zones:update-summaries';
    protected $description = 'Actualiza los resúmenes de medidas actuales y diarias de todas las zonas desde la API de Wiseconn';

    public function handle(): void
    {
        $zones = Zone::with('field')->whereJsonContains('type', 'Weather')->get();

        foreach ($zones as $zone) {
            if ($zone->field) {
                UpdateZoneSummariesJob::dispatch($zone, $zone->field)->onQueue('summaries');
            }
        }

        $this->info('Jobs para actualizar resúmenes de zonas despachados exitosamente.');
    }
}