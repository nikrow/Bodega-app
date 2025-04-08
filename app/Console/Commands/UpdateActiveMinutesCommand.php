<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateActiveMinutesCommand extends Command
{
    protected $signature = 'users:update-active-minutes';
    protected $description = 'Update active minutes for users who did not logout properly';

    public function handle()
    {
        // Define el límite de inactividad
        $inactivityLimit = now()->subMinutes(config('session.lifetime'));

        // Obtén los usuarios inactivos
        $users = User::whereNotNull('last_login_at')
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<', $inactivityLimit)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No inactive users found to update.');
            return;
        }

        // Inicia una transacción para asegurar consistencia
        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                // Calcula los minutos activos
                $minutesActive = $user->last_login_at->diffInMinutes($user->last_activity_at);

                // Actualiza el usuario en una sola consulta
                $user->update([
                    'active_minutes' => DB::raw("active_minutes + {$minutesActive}"),
                    'last_login_at' => null,
                    'last_activity_at' => null,
                ]);
            }
        });

        $this->info('Active minutes updated for ' . $users->count() . ' inactive users.');
    }
}