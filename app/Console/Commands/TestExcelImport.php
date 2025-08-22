<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessExcel;

class TestExcelImport extends Command
{
    protected $signature = 'test:excel {path=test.xls} {tenant=1}';
    protected $description = 'Probar importación de Excel localmente';

    public function handle()
    {
        $path = $this->argument('path');
        $tenant = $this->argument('tenant');

        // Ejecutar job sin pasar por la cola
        (new ProcessExcel($path, $tenant))->handle();

        $this->info("Importación ejecutada sobre {$path} con tenant {$tenant}");
    }
}
