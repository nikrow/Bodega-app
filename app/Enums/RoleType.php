<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RoleType: string implements HasColor, HasIcon, HasLabel
{
    case ADMIN = 'admin';
    case AGRONOMO = 'agronomo';
    case BODEGUERO = 'bodeguero';
    case ASISTENTE = 'asistente';
    case ESTANQUERO = 'estanquero';
    case USUARIO = 'usuario';
    case OPERARIO = 'operario';
    case USUARIOMAQ = 'usuarioMaquinaria';
    case MAQUINARIA = 'maquinaria';
    case SUPERUSER = 'superuser';
    case SUPERVISOR = 'supervisor';

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => __('admin'),
            self::AGRONOMO => __('agronomo'),
            self::BODEGUERO => __('bodeguero'),
            self::ASISTENTE => __('asistente'),
            self::ESTANQUERO => __('estanquero'),
            self::USUARIO => __('usuario'),
            self::OPERARIO => __('operario'),
            self::USUARIOMAQ => __('usuarioMaquinaria'),
            self::MAQUINARIA => __('maquinaria'),
            self::SUPERUSER => __('superusuario'),
            self::SUPERVISOR => __('supervisor'),
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::ADMIN => 'heroicon-o-user-circle',
            self::AGRONOMO => 'heroicon-o-user-group',
            self::BODEGUERO => 'heroicon-o-user-group',
            self::ASISTENTE => 'heroicon-o-user-group',
            self::ESTANQUERO => 'heroicon-o-user-group',
            self::USUARIO => 'heroicon-o-user-group',
            self::OPERARIO => 'heroicon-o-user-group',
            self::USUARIOMAQ => 'heroicon-o-user-group',
            self::MAQUINARIA => 'heroicon-o-user-group',
            self::SUPERUSER => 'heroicon-o-user-circle',
            self::SUPERVISOR => 'heroicon-o-user-group',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ADMIN => 'success',
            self::AGRONOMO => 'warning',
            self::BODEGUERO => 'info',
            self::ASISTENTE => 'primary',
            self::ESTANQUERO => 'secondary',
            self::USUARIO => 'danger',
            self::OPERARIO => 'success',
            self::USUARIOMAQ => 'success',
            self::MAQUINARIA => 'success',
            self::SUPERUSER => 'danger',
            self::SUPERVISOR => 'warning',
        };
    }
}
