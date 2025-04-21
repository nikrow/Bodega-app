<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Illuminate\Support\Facades\Notification;


//Programar tareas

Schedule::command('backup:run')
    ->daily()
    ->at('01:30')
    ->onFailure(function () {
        Notification::route('mail', 'plataformagjs@gmail.com')
            ->notify(new BackupHasFailedNotification());
    })
    ->onSuccess(function () {
        Log::info('Backup completed successfully at ' . now());
    });
    Schedule::command('backup:monitor')->daily()->at('02:00');