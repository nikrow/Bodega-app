<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProviderType: string implements HasColor, HasIcon, HasLabel
{

    case JorgeSchmidt = 'Jorge Schmidt';
    case Bemat = 'Bemat';
    case TractorAmarillo = 'Tractor Amarillo';
    case Fedemaq = 'Fedemaq';
    case SchmditHermanos = 'Schmdit Hermanos';
    case MayolYPiraino = 'Mayol y Piraino';
    case Otro = 'Otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::JorgeSchmidt => __('Jorge Schmidt'),
            self::Bemat => __('Bemat'),
            self::TractorAmarillo => __('Tractor Amarillo'),
            self::Fedemaq => __('Fedemaq'),
            self::SchmditHermanos => __('Schmdit Hermanos'),
            self::MayolYPiraino => __('Mayol y Piraino'),
            self::Otro => __('Otro'),
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::JorgeSchmidt => 'phosphor-tractor-light',
            self::Bemat => 'phosphor-tractor-light',
            self::TractorAmarillo => 'phosphor-tractor-light',
            self::Fedemaq => 'phosphor-tractor-light',
            self::SchmditHermanos => 'phosphor-tractor-light',
            self::MayolYPiraino => 'phosphor-tractor-light',
            self::Otro => 'phosphor-tractor-light',
        };
    }
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::JorgeSchmidt => 'primary',
            self::Bemat => 'info',
            self::TractorAmarillo => 'warning',
            self::Fedemaq => 'danger',
            self::SchmditHermanos => 'info',
            self::MayolYPiraino => 'info',
            self::Otro => 'info',
        };
    }
    
}
