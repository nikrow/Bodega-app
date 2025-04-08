<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Spatie\Backup\Exceptions\BackupFailed;
use Illuminate\Support\Facades\Notification;


//Programar tareas
Schedule::command('users:update-active-minutes')->everyFourHours();

Schedule::command('backup:clean')->daily()->at('01:00');

Schedule::command('backup:run')
->daily()
->at('01:30')
->onFailure(function () {
    Notification::route('mail', 'admin@example.com')
        ->notify(new BackupFailed());
})
->onSuccess(function () {
    Log::info('Backup completed successfully at ' . now());
});
Schedule::command('backup:monitor')->hourly();