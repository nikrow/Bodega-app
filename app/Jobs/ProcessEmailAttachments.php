<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessExcel;

class ProcessEmailAttachments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
{
    Log::info('Iniciando ProcessEmailAttachments');
    $client = Client::account('default');

    $client->connect();

    $folder = $client->getFolder('INBOX');
    $messages = $folder->query()->unseen()->subject('Reporte Diario de Riego')->get();

    foreach ($messages as $message) {
        $emailBody = $message->getTextBody();
        preg_match('/Field(?: ID)?: (\w+)/', $emailBody, $matches);
        $tenantId = $matches[1] ?? null;
        Log::info('Tenant ID extraído:', ['tenant_id' => $tenantId]);
        
        foreach ($message->getAttachments() as $attachment) {
            $ext = strtolower($attachment->getExtension());
            if (!in_array($ext, ['xls', 'xlsx'])) {
                continue;
            }

            // Generar un nombre de archivo único
            $filename = sprintf('reporte_%s_%s.%s', time(), uniqid(), $ext);
            $path = 'excels/' . $filename;

            // Obtener el contenido del adjunto primero
            $content = $attachment->getContent();
            Log::info('Primeros bytes del contenido', ['content_start' => substr($content, 0, 10)]);

            // Verificar si el contenido está vacío
            if (empty($content)) {
                Log::error('El contenido del adjunto está vacío', ['filename' => $filename]);
                continue;
            }

            // Guardar el archivo con Storage::put
            Storage::disk('local')->put($path, $content);
            Log::info('Archivo guardado con Storage::put', ['path' => $path]);

            // Verificar si el archivo existe
            $fullPath = storage_path('app/' . $path);
            if (!file_exists($fullPath)) {
                Log::error('El archivo no existe después de guardar', ['full_path' => $fullPath]);
                continue;
            }

            // Despachar el job ProcessExcel
            ProcessExcel::dispatch($path, $tenantId);
            Log::info('ProcessExcel despachado', ['path' => $path, 'tenant' => $tenantId]);
            $message->setFlag('Seen');
        }
    }

    $client->disconnect();
}
}
