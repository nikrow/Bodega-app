<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SensorType: string implements HasColor, HasIcon, HasLabel
{
    case TEMPERATURE = 'Temperature';
    case RAIN = 'Rain';
    case HUMIDITY = 'Humidity';
    case WIND_GUST = 'Wind Gust';
    case WIND_VELOCITY = 'Wind Velocity';
    case SOLAR_RADIATION = 'Solar Radiation';
    case WIND_DIRECTION = 'Wind Direction';
    case CHILL_HOURS_DAILY = 'Chill Hours (Daily)';
    case CHILL_HOURS_ACCUMULATED = 'Chill Hours (Accumulated)';

    public function getLabel(): string
    {
        return match ($this) {
            self::TEMPERATURE => __('Temperature'),
            self::RAIN => __('Rain'),
            self::HUMIDITY => __('Humidity'),
            self::SOIL_MOISTURE => __('Soil Moisture'),
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::TEMPERATURE => 'heroicon-o-sun',
            self::RAIN => 'heroicon-o-cloud-rain',
            self::HUMIDITY => 'heroicon-o-droplet',
            self::SOIL_MOISTURE => 'heroicon-o-water',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TEMPERATURE => 'warning',
            self::RAIN => 'info',
            self::HUMIDITY => 'primary',
            self::SOIL_MOISTURE => 'success',
        };
    }
}