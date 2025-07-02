<?php
use App\Models\Zone;
use App\Models\Field;
use App\Services\WiseconnService;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessEmailAttachments;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Notification;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;


//Programar tareas

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
Schedule::call(function () {
    $field = Field::find(1); 
    if ($field) {
                app(WiseconnService::class)->syncZones($field);
            } else {
                Log::warning('No se encontro ningun campo para sincronizar zonas.');
            }
        })->hourly();
