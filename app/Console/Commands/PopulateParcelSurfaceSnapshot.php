<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderApplication;

class PopulateParcelSurfaceSnapshot extends Command
{
    protected $signature = 'order-applications:populate-parcel-surface-snapshot';
    protected $description = 'Populate parcel_surface_snapshot column for existing OrderApplication records';

    public function handle()
    {
        $this->info('Populating parcel_surface_snapshot for OrderApplication records...');

        OrderApplication::with('parcel')->chunk(100, function ($applications) {
            foreach ($applications as $application) {
                if ($application->parcel) {
                    // Actualizar sin disparar eventos y forzar updated_by a 1
                    $application->updateQuietly([
                        'parcel_surface_snapshot' => $application->parcel->surface ?? 0,
                        'updated_by' => 1, // Usuario ID 1 como predeterminado
                    ]);
                }
            }
        });

        $this->info('Completed populating parcel_surface_snapshot.');
    }
}