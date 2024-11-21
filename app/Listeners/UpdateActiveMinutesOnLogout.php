<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\User;

class UpdateActiveMinutesOnLogout
{
    /**
     * Maneja el evento de logout.
     */
    public function handle(Logout $event)
    {
        /** @var User $user */
        $user = $event->user;

        if ($user && $user->last_login_at && $user->last_activity_at) {
            // Asegúrate de que last_login_at y last_activity_at sean instancias de Carbon
            $minutesActive = $user->last_login_at->diffInMinutes($user->last_activity_at);
            $user->increment('active_minutes', $minutesActive);
            $user->update([
                'last_login_at' => null,
                'last_activity_at' => null,
            ]);
        }
    }
}
