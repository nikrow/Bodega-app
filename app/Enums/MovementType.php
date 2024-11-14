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
    case APPLICATION_USAGE = 'application_usage';
    case APPLICATION_USAGE_UPDATE = 'application_usage_update';
    case APPLICATION_USAGE_DELETED = 'application_usage_deleted';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ENTRADA => 'success',
            self::SALIDA => 'danger',
            self::TRASLADO => 'warning',
            self::APPLICATION_USAGE => 'info',
            self::APPLICATION_USAGE_UPDATE => 'info',
            self::APPLICATION_USAGE_DELETED => 'danger',
        };
    }
    public function getLabel(): string
    {
        return match ($this) {
            self::ENTRADA => __('entrada'),
            self::SALIDA => __('salida'),
            self::TRASLADO => __('traslado'),
            self::APPLICATION_USAGE => __('application_usage'),
            self::APPLICATION_USAGE_UPDATE => __('application_usage_update'),
            self::APPLICATION_USAGE_DELETED => __('application_usage_deleted'),
        };

        }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ENTRADA => 'heroicon-o-arrow-up-right',
            self::SALIDA => 'heroicon-o-arrow-down-right',
            self::TRASLADO => 'eos-swap-horiz',
            self::APPLICATION_USAGE => 'heroicon-o-arrow-up',
            self::APPLICATION_USAGE_UPDATE => 'heroicon-o-arrow-up',
            self::APPLICATION_USAGE_DELETED => 'heroicon-o-trash',
        };
    }

}
