<?php

namespace App\Jobs;

use App\Models\Zone;
use App\Services\WiseconnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateZoneSummariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WiseconnService $wiseconnService): void
    {
        Log::info('Iniciando actualización de zone_summaries para todas las zonas.');
        $zones = Zone::all(); // Fetch all zones, not just Weather
        foreach ($zones as $zone) {
            $field = $zone->field;
            if ($field) {
                Log::info("Processing zone: {$zone->name} (ID: {$zone->id})");
                $wiseconnService->updateZoneSummary($field, $zone);
            } else {
                Log::warning("No field found for zone {$zone->name} (ID: {$zone->id})");
            }
        }
        Log::info('Actualización de zone_summaries completada.');
    }
}