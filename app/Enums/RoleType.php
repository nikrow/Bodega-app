<?php

namespace App\Enums;

enum RoleType: string
{
    case ADMIN = 'admin';
    case AGRONOMO = 'agronomo';
    case BODEGUERO = 'bodeguero';
    case ASISTENTE = 'asistente';
    case ESTANQUERO = 'estanquero';
    case USUARIO = 'usuario';
}
