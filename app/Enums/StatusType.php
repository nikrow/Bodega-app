<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusType: string implements HasColor, HasIcon, HasLabel
{
    case PENDIENTE = 'Pendiente';
    case ENPROCESO = 'En proceso';
    case COMPLETO = 'Completo';
    case CANCELADO = 'Cancelado';

    public static function getValues(): array
    {
        return [
            self::PENDIENTE->value => 'Pendiente',
            self::ENPROCESO->value => 'En proceso',
            self::COMPLETO->value => 'Completo',
            self::CANCELADO->value => 'Cancelado',
        ];
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE => 'warning',
            self::ENPROCESO => 'info',
            self::COMPLETO => 'success',
            self::CANCELADO => 'danger',
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'heroicon-o-clock',
            self::ENPROCESO => 'heroicon-s-truck',
            self::COMPLETO => 'heroicon-o-check',
            self::CANCELADO => 'heroicon-o-x-circle',
        };
    }
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::ENPROCESO => 'En proceso',
            self::COMPLETO => 'Completo',
            self::CANCELADO => 'Cancelado',
        };
    }
}
