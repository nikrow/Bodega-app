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
    case TRASLADO_ENTRADA = 'traslado-entrada';
    case TRASLADO_SALIDA = 'traslado-salida';
    case TRASLADO_CAMPOS = 'traslado-campos';


    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ENTRADA => 'success',
            self::SALIDA => 'danger',
            self::TRASLADO => 'warning',
            self::PREPARACION => 'danger',
            self::TRASLADO_ENTRADA => 'warning',
            self::TRASLADO_SALIDA => 'warning',
            self::TRASLADO_CAMPOS => 'info',
        };
    }
    public function getLabel(): string
    {
        return match ($this) {
            self::ENTRADA => __('entrada'),
            self::SALIDA => __('salida'),
            self::TRASLADO => __('traslado'),
            self::PREPARACION => __('preparación'),
            self::TRASLADO_ENTRADA => __('Traslado entrada'),
            self::TRASLADO_SALIDA => __('Traslado salida'),
            self::TRASLADO_CAMPOS => __('Traslado campos'),

        };

        }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ENTRADA => 'heroicon-o-arrow-up-right',
            self::SALIDA => 'heroicon-o-arrow-down-right',
            self::TRASLADO => 'eos-swap-horiz',
            self::PREPARACION => 'heroicon-m-arrow-long-right',
            self::TRASLADO_ENTRADA => 'heroicon-o-arrow-right',
            self::TRASLADO_SALIDA => 'heroicon-o-arrow-left',
            self::TRASLADO_CAMPOS => 'heroicon-o-arrow-path',
        };
    }

}