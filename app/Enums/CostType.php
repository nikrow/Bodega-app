<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CostType: string implements HasColor, HasIcon, HasLabel
{
    case cerezos = 'cerezos';
    case paltos = 'paltos';
    case mandarinos = 'mandarinos';
    case general = 'general';

    public function getLabel(): string
    {
        return match ($this) {
            self::cerezos => __('Cerezos'),
            self::paltos => __('Paltos'),
            self::mandarinos => __('Mandarinos'),
            self::general => __('General'),
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::cerezos => 'phosphor-cherries-light',
            self::paltos => 'phosphor-avocado-light',
            self::mandarinos => 'phosphor-orange-light',
            self::general => 'phosphor-road-horizon-light',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::cerezos => 'danger',
            self::paltos => 'primary',
            self::mandarinos => 'warning',
            self::general => 'info',
        };
    }
}
