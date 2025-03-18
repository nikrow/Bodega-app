<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EppType: string implements HasColor, HasIcon, HasLabel
{
    case TRAJEAPLICAION = 'traje_aplicacion';
    case GUANTES = 'guantes';
    case BOTAS = 'botas';
    case PROTECTORAUDITIVO = 'protector_auditivo';
    case ANTEOJOS = 'anteojos';
    case ANTIPARRAS = 'antiparras';
    case MASCARAFILTRO = 'mascara_filtro';

    public static function getValues(): array
    {
        return [
            self::TRAJEAPLICAION->value => 'Traje de Aplicación',
            self::GUANTES->value => 'Guantes',
            self::BOTAS->value => 'Botas',
            self::PROTECTORAUDITIVO->value => 'Protector Auditivo',
            self::ANTEOJOS->value => 'Anteojos',
            self::ANTIPARRAS->value => 'Antiparras',
            self::MASCARAFILTRO->value => 'Mascara con Filtro',
        ];
    }
    public static function options(): array
    {
        return [
            self::TRAJEAPLICAION->value => 'Traje de Aplicación',
            self::GUANTES->value => 'Guantes',
            self::BOTAS->value => 'Botas',
            self::PROTECTORAUDITIVO->value => 'Protector Auditivo',
            self::ANTEOJOS->value => 'Anteojos',
            self::ANTIPARRAS->value => 'Antiparras',
            self::MASCARAFILTRO->value => 'Mascara con Filtro',
        ];
    }
    public function getLabel(): string
    {
        return match ($this) {
            self::TRAJEAPLICAION => 'Traje de Aplicación',
            self::GUANTES => 'Guantes',
            self::BOTAS => 'Botas',
            self::PROTECTORAUDITIVO => 'Protector Auditivo',
            self::ANTEOJOS => 'Anteojos',
            self::ANTIPARRAS => 'Antiparras',
            self::MASCARAFILTRO => 'Mascara con Filtro',
        };
    }
    public function getColor(): string
    {
        return match ($this) {
            self::TRAJEAPLICAION => 'danger',
            self::GUANTES => 'success',
            self::BOTAS => 'primary',
            self::PROTECTORAUDITIVO => 'warning',
            self::ANTEOJOS => 'info',
            self::ANTIPARRAS => 'secondary',
            self::MASCARAFILTRO => 'success',
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::TRAJEAPLICAION => 'heroicon-o-user',
            self::GUANTES => 'heroicon-o-hand',
            self::BOTAS => 'heroicon-o-shoe',
            self::PROTECTORAUDITIVO => 'heroicon-o-head',
            self::ANTEOJOS => 'heroicon-o-eye',
            self::ANTIPARRAS => 'heroicon-o-glasses',
            self::MASCARAFILTRO => 'heroicon-o-mask',
        };
    }
    

}
