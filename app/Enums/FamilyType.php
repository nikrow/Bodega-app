<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FamilyType: string implements HasLabel, HasColor, HasIcon
{
    case INSECTICIDA = 'insecticida';
    case HERBICIDA = 'herbicida';
    case FERTILIZANTE = 'fertilizante';
    case ACARICIDA = 'acaricida';
    case FUNGICIDA = 'fungicida';
    case BIOESTIMULANTE = 'bioestimulante';
    case REGULADOR = 'regulador';
    case BLOQUEADOR = 'bloqueador';

    public function getLabel(): string
    {
        return match ($this) {
            self::INSECTICIDA => 'Insecticida',
            self::HERBICIDA => 'Herbicida',
            self::FERTILIZANTE => 'Fertilizante',
            self::ACARICIDA => 'Acaricida',
            self::FUNGICIDA => 'Fungicida',
            self::BIOESTIMULANTE => 'Bioestimulante',
            self::REGULADOR => 'Regulador',
            self::BLOQUEADOR => 'Bloqueador',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::INSECTICIDA => 'danger',
            self::HERBICIDA => 'success',
            self::FERTILIZANTE => 'primary',
            self::ACARICIDA => 'warning',
            self::FUNGICIDA => 'info',
            self::BIOESTIMULANTE => 'secondary',
            self::REGULADOR => 'success',
            self::BLOQUEADOR => 'dark',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::INSECTICIDA => 'heroicon-o-bug',
            self::HERBICIDA => 'heroicon-o-sparkles',
            self::FERTILIZANTE => 'heroicon-o-flower',
            self::ACARICIDA => 'heroicon-o-shield-check',
            self::FUNGICIDA => 'heroicon-o-cloud',
            self::BIOESTIMULANTE => 'heroicon-o-leaf',
            self::REGULADOR => 'heroicon-o-arrow-circle-up',
            self::BLOQUEADOR => 'heroicon-o-lock-closed',
        };
    }
}
