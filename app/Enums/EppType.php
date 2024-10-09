<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EppType: string
{
    case TRAJEAPLICAION = 'traje_aplicacion';
    case GUANTES = 'guantes';
    case BOTAS = 'botas';
    case PROTECTORAUDITIVO = 'protector_auditivo';
    case ANTEOJOS = 'anteojos';
    case ANTIPARRAS = 'antiparras';
    case MASCARAFILtro = 'mascara_filtro';
}
