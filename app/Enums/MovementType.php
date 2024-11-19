<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MovementType: string implements HasColor, HasIcon, HasLabel
{
    case ENTRADA = 'entrada';
    case SALIDA = 'salida';
    case TRASLADO = 'traslado';
    case PREPARACION = 'preparacion';


    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ENTRADA => 'success',
            self::SALIDA => 'danger',
            self::TRASLADO => 'warning',
            self::PREPARACION => 'danger',
        };
    }
    public function getLabel(): string
    {
        return match ($this) {
            self::ENTRADA => __('entrada'),
            self::SALIDA => __('salida'),
            self::TRASLADO => __('traslado'),
            self::PREPARACION => __('preparación'),
        };

        }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ENTRADA => 'heroicon-o-arrow-up-right',
            self::SALIDA => 'heroicon-o-arrow-down-right',
            self::TRASLADO => 'eos-swap-horiz',
            self::PREPARACION => 'heroicon-m-arrow-long-right',
        };
    }

}
