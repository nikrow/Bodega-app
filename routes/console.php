<?php

use App\Console\Commands\BackupDatabaseCommand;
use App\Models\Zone;
use App\Models\Field;
use Dompdf\Image\Cache;
use App\Jobs\CacheClimateDataJob;
use App\Services\WiseconnService;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateZoneSummariesJob;
use App\Jobs\ProcessEmailAttachments;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\UpdateHistoricalMeasuresJob; 
use Illuminate\Support\Facades\Notification;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;

Schedule::command('backup:run')
    ->daily()
    ->at('01:30')
    ->onFailure(function () {
        Notification::route('mail', 'plataformagjs@gmail.com');
    })
    ->onSuccess(function () {
        Log::info('Backup completed successfully at ' . now());
    });

Schedule::command('backup:monitor')->daily()->at('03:00');

Schedule::command(BackupDatabaseCommand::class)
    ->daily()
    ->at('02:00')
    ->onFailure(function () {
        Log::error('Backup de base de datos fallido a las ' . now());
    })
    ->onSuccess(function () {
        Log::info('Backup de base de datos exitoso a las ' . now());
    });

Schedule::job(new ProcessEmailAttachments())->dailyAt('08:05')
    ->onFailure(function () {
        Log::error('Fallo en procesamiento de archivo adjunto ' . now());
    })
    ->onSuccess(function () {
        Log::info('Archivo adjunto procesado con éxito ' . now());
    });
    
Schedule::command('zones:update-summaries')
    ->everyFifteenMinutes() 
    ->withoutOverlapping() // Evita que se ejecuten múltiples instancias del comando al mismo tiempo
    ->onFailure(function () {
        Log::error('Fallo en actualización de resúmenes de zonas a las ' . now());
    })
    ->onSuccess(function () {
        Log::info('Resúmenes de zonas actualizados con éxito a las ' . now());
    });
    
Schedule::job(new CacheClimateDataJob())->everyFiveMinutes()
    ->onFailure(function () {
        Log::error('Fallo en caché de datos climáticos ' . now());
    })
    ->onSuccess(function () {
        Log::info('Datos climáticos cacheados con éxito ' . now());
    });

Schedule::call(function () {
    Log::info('Iniciando sincronización de zonas para todos los campos.');
    $fields = Field::all(); // Obtener todos los campos
    if ($fields->isEmpty()) {
        Log::warning("No se encontraron campos para sincronizar zonas.");
        return;
    }

    $wiseconnService = app(WiseconnService::class);
    foreach ($fields as $field) {
        try {
            // Validar la API key antes de intentar sincronizar las zonas de un campo
            $validation = $wiseconnService->validateApiKey($field);
            if (!$validation['valid']) {
                Log::error("Saltando sincronización de zonas para el Campo ID {$field->id} (Nombre: {$field->name}) debido a API Key inválida: {$validation['message']}");
                continue;
            }

            $wiseconnService->syncZones($field);
            Log::info("Sincronización de zonas para el Campo ID {$field->id} (Nombre: {$field->name}) completada exitosamente.");
        } catch (\Exception $e) {
            Log::error("Error al sincronizar zonas para el Campo ID {$field->id} (Nombre: {$field->name}): {$e->getMessage()}");
        }
    }
    Log::info('Sincronización de zonas para todos los campos finalizada.');
})->hourly(); 