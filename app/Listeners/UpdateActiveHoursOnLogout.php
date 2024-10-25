<?php
namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class UpdateActiveHoursOnLogout
{
    public function handle(Logout $event)
    {
        $user = $event->user;
        if ($user && $user->last_login_at) {
            $hoursActive = now()->diffInMinutes($user->last_login_at) / 60;
            $user->increment('active_hours', $hoursActive);
            $user->update(['last_login_at' => null]); // Reset last login after updating
        }
    }
}
