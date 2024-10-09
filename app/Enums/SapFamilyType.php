<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SapFamilyType: string
{
    case FERTILIZANTES = 'fertilizantes-enmiendas';
    case FITOSANITARIOS = 'fitosanitarios';
    case FITOREGULADORES = 'fitoreguladores';
    case BIOESTIMULANTES = 'bioestimulantes';
    case OTROS = 'otros';

}
