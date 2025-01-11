<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Events\IccProDataUpdated;

class WaterLiveController extends Controller
{
    /**
     * Muestra el dashboard principal con Inertia.
     */
    public function index()
    {
        // Disparar un evento de prueba
        event(new IccProDataUpdated('¡Datos de riego actualizados en tiempo real!'));

        // Retornar la vista usando Inertia
        return Inertia::render('WaterLive');
    }
}
