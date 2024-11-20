<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Opcional: Actualizar solo si han pasado más de 1 minuto desde la última actividad
            if (now()->diffInMinutes($user->last_activity_at) >= 1) {
                $user->update(['last_activity_at' => now()]);
            }
        }

        return $next($request);
    }
}
