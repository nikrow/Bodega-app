<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\User;

class UpdateActiveMinutesOnLogout
{
    public function handle(Logout $event)
    {
        /** @var User $user */
        $user = $event->user;

        if ($user && $user->last_login_at && $user->last_activity_at) {
            $minutesActive = $user->last_login_at->diffInMinutes($user->last_activity_at);
            $user->increment('active_minutes', $minutesActive);
            $user->update([
                'last_login_at' => null,
                'last_activity_at' => null,
            ]);
        }
    }
}
