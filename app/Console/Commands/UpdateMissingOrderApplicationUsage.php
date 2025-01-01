<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderApplication;
use App\Models\OrderApplicationUsage;
use App\Observers\OrderApplicationObserver;
use Illuminate\Support\Facades\Log;

class UpdateMissingOrderApplicationUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:missing-order-application-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza únicamente los registros faltantes de OrderApplicationUsage basándose en OrderApplications existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando registros faltantes en OrderApplicationUsage...');

        // Obtener OrderApplications cuyo ID no está en OrderApplicationUsage
        $missingOrderApplications = OrderApplication::whereDoesntHave('orderApplicationUsage')->get();

        if ($missingOrderApplications->isEmpty()) {
            $this->info('No hay registros faltantes para actualizar.');
            return;
        }

        $observer = new OrderApplicationObserver();

        foreach ($missingOrderApplications as $orderApplication) {
            try {
                $this->info('Procesando OrderApplication ID: ' . $orderApplication->id);

                // Llamar al método del Observer para cada registro
                $observer->updated($orderApplication);

                $this->info('Actualización completada para OrderApplication ID: ' . $orderApplication->id);
            } catch (\Exception $e) {
                Log::error('Error al procesar OrderApplication ID: ' . $orderApplication->id . '. Error: ' . $e->getMessage());
                $this->error('Error al procesar OrderApplication ID: ' . $orderApplication->id);
            }
        }

        $this->info('Proceso de actualización de registros faltantes completado.');
    }
}
