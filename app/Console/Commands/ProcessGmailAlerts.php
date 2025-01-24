<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlarmaRiego;
use App\Services\GmailService;

class ProcessGmailAlerts extends Command
{
    protected $signature = 'gmail:process-alerts';
    protected $description = 'Procesa correos de alerta de riego y los guarda en la base de datos.';

    public function handle()
    {
        $gmailService = new GmailService();
        $messages = $gmailService->getMessages();

        foreach ($messages as $message) {
            $content = $gmailService->getMessageDetails($message->getId());

            if (str_contains($content, 'Alarma Alto Caudal')) {
                preg_match('/Programa de Irrigacion (.*?) /', $content, $programaMatch);
                preg_match('/Cauda: (\d+)/', $content, $caudalMatch);
                preg_match('/Expectativo (\d+)/', $content, $esperadoMatch);

                if ($programaMatch && $caudalMatch && $esperadoMatch) {
                    $programa = $programaMatch[1];
                    $caudal = (int)$caudalMatch[1];
                    $esperado = (int)$esperadoMatch[1];
                    $diferencia = (($caudal - $esperado) / $esperado) * 100;

                    AlarmaRiego::create([
                        'programa_irrigacion' => $programa,
                        'alarma_tipo' => 'Alto Caudal',
                        'caudal' => $caudal,
                        'esperado' => $esperado,
                        'diferencia_porcentaje' => $diferencia,
                    ]);

                    $this->info("Alarma registrada: $programa");
                }
            }
        }
    }
}
