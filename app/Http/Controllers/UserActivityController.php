<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSession;

class UserActivityController extends Controller
{
    public function heartbeat(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Obtener la sesión activa más reciente sin logout_at
            $session = UserSession::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first();

            if ($session && $session->login_at) {
                // Incrementar active_minutes
                $session->increment('active_minutes');
                // Incrementar active_minutes en el usuario
                $user->increment('active_minutes');
                // Actualizar el timestamp 'updated_at' de la sesión
                $session->touch();
            }
        }

        return response()->json(['status' => 'success']);
    }
}
