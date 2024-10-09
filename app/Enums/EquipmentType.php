<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EquipmentType: string
{
    case TURBONEBULIZADOR = 'turbonebulizador';
    case TURBOCANON = 'turbocañon';
    case HELICOPTERO = 'helicoptero';
    case DRON = 'dron';
    case CARACOL = 'caracol';
    case BOMBAESPALDA = 'bomba_espalda';
    case BARRA = 'barra_levera_parada';
    case AZUFRADOR = 'azufrador';

}
