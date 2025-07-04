<?php
use App\Models\Field;
use App\Services\WiseconnService;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessEmailAttachments;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Notification;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use App\Jobs\UpdateZoneSummariesJob;
use App\Jobs\UpdateHistoricalMeasuresJob; 
use App\Models\Zone;

Schedule::command('backup:run')
    ->daily()
    ->at('01:30')
    ->onFailure(function () {
        Notification::route('mail', 'plataformagjs@gmail.com');
    })
    ->onSuccess(function () {
        Log::info('Backup completed successfully at ' . now());
    });
    Schedule::command('backup:monitor')->daily()->at('02:00');

Schedule::job(new ProcessEmailAttachments())->dailyAt('08:05')
    ->everyFiveMinutes()
    ->onFailure(function () {
        Log::error('Fallo en procesamiento de archivo adjunto ' . now());
    })
    ->onSuccess(function () {
        Log::info('Archivo adjunto procesado con éxito ' . now());
    });
Schedule::command('zones:update-summaries')->everyFiveMinutes();
Schedule::command('zones:update-historical-measures')->everyFiveMinutes();
Schedule::call(function () {
    $field = Field::find(1); 
    if ($field) {
        try {
            app(WiseconnService::class)->syncZones($field);
            Log::info("Sincronización de zonas para el Field ID {$field->id} completada exitosamente.");
        } catch (\Exception $e) {
            Log::error("Error al sincronizar zonas para el Field ID {$field->id}: {$e->getMessage()}");
        }
    } else {
        Log::warning("No se encontró el Field con ID 1 para sincronizar zonas.");
    }
})->hourly();