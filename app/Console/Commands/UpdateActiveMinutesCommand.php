<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class UpdateActiveMinutesCommand extends Command
{
    protected $signature = 'users:update-active-minutes';
    protected $description = 'Update active minutes for users who did not logout properly';

    public function handle()
    {
        $users = User::whereNotNull('last_login_at')
            ->whereNotNull('last_activity_at')
            ->get();

        foreach ($users as $user) {
            // Considerar un tiempo de inactividad para determinar si el usuario se desconectó
            $inactivityLimit = now()->subMinutes(config('session.lifetime'));

            if ($user->last_activity_at < $inactivityLimit) {
                $minutesActive = $user->last_login_at->diffInMinutes($user->last_activity_at);
                $user->increment('active_minutes', $minutesActive);
                $user->update([
                    'last_login_at' => null,
                    'last_activity_at' => null,
                ]);
            }
        }

        $this->info('Active minutes updated for inactive users.');
    }
}
