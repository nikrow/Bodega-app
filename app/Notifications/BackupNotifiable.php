<?php

namespace App\Notifications;

use Spatie\Backup\Notifications\Notifiable as SpatieNotifiable;

class BackupNotifiable extends SpatieNotifiable
{
    public function routeNotificationForSlack()
    {
        return config('services.slack.notifications.channel');
    }
}