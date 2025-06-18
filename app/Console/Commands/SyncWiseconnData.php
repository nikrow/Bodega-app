<?php

namespace App\Console\Commands;

use App\Models\Field;
use App\Models\Zone;
use App\Services\WiseconnService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWiseconnData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiseconn:sync-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza zonas y datos históricos de Wiseconn para todos los campos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de datos de Wiseconn...');

        $fields = Field::all(); // O Field::whereNotNull('api_key')->get();

        if ($fields->isEmpty()) {
            $this->warn('No se encontraron campos para sincronizar.');
            return Command::FAILURE;
        }

        $wiseconnService = new WiseconnService();

        foreach ($fields as $field) {
            $this->info("Procesando campo: {$field->name} (ID: {$field->id})");

            try {
                // Sincronizar zonas
                $this->info("Sincronizando zonas para {$field->name}...");
                $wiseconnService->syncZones($field);
                $this->info("Zonas sincronizadas para {$field->name}.");

                // Inicializar datos históricos para las zonas del campo
                $zones = Zone::where('field_id', $field->id)->get();
                if ($zones->isEmpty()) {
                    $this->warn("No se encontraron zonas para inicializar datos históricos en {$field->name}.");
                    continue;
                }

                foreach ($zones as $zone) {
                    $this->info("Inicializando datos históricos para la zona: {$zone->name} (ID: {$zone->id})...");
                    $wiseconnService->initializeHistoricalMeasures($field, $zone);
                    $this->info("Datos históricos inicializados para la zona: {$zone->name}.");
                }
            } catch (\Exception $e) {
                $this->error("Error al procesar el campo {$field->name}: {$e->getMessage()}");
                Log::error("Error en SyncWiseconnData para el campo {$field->name}:", ['exception' => $e]);
            }
        }

        $this->info('Sincronización de datos de Wiseconn completada.');
        return Command::SUCCESS;
    }
}