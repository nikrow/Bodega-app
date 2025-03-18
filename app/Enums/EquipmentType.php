<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EquipmentType: string implements HasColor, HasIcon, HasLabel
{
    case TURBONEBULIZADOR = 'turbonebulizador';
    case TURBOCANON = 'turbocañon';
    case HELICOPTERO = 'helicoptero';
    case DRON = 'dron';
    case CARACOL = 'caracol';
    case BOMBAESPALDA = 'bomba_espalda';
    case BARRA = 'barra_levera_parada';
    case AZUFRADOR = 'azufrador';
    case PITON = 'piton';
    case BARRA_PULVERIZACION = 'barra_pulverizacion';
    case VIA_RIEGO = 'via_riego';
    case INYECCION = 'inyeccion';

    public static function getValues(): array
    {
        return [
            self::TURBONEBULIZADOR->value => 'Turbonebulizador',
            self::TURBOCANON->value => 'Turbocañon',
            self::HELICOPTERO->value => 'Helicoptero',
            self::DRON->value => 'Dron',
            self::CARACOL->value => 'Caracol',
            self::BOMBAESPALDA->value => 'Bomba de Espalda',
            self::BARRA->value => 'Barra Levera Parada',
            self::AZUFRADOR->value => 'Azufrador',
            self::PITON->value => 'Pitón',
            self::BARRA_PULVERIZACION->value => 'Barra de Pulverización',
            self::VIA_RIEGO->value => 'Vía Riego',
            self::INYECCION->value => 'Inyección',
        ];
    }
    /**
     * Obtener las opciones para el formulario.
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::TURBONEBULIZADOR->value => 'Turbonebulizador',
            self::TURBOCANON->value => 'Turbocañon',
            self::HELICOPTERO->value => 'Helicoptero',
            self::DRON->value => 'Dron',
            self::CARACOL->value => 'Caracol',
            self::BOMBAESPALDA->value => 'Bomba de Espalda',
            self::BARRA->value => 'Barra Levera Parada',
            self::AZUFRADOR->value => 'Azufrador',
            self::PITON->value => 'Pitón',
            self::BARRA_PULVERIZACION->value => 'Barra de Pulverización',
            self::VIA_RIEGO->value => 'Vía Riego',
            self::INYECCION->value => 'Inyección',
        ];
    }
    public function getLabel(): string
    {
        return match ($this) {
            self::TURBONEBULIZADOR => 'Turbonebulizador',
            self::TURBOCANON => 'Turbocañon',
            self::HELICOPTERO => 'Helicoptero',
            self::DRON => 'Dron',
            self::CARACOL => 'Caracol',
            self::BOMBAESPALDA => 'Bomba de Espalda',
            self::BARRA => 'Barra Levera Parada',
            self::AZUFRADOR => 'Azufrador',
            self::PITON => 'Pitón',
            self::BARRA_PULVERIZACION => 'Barra de Pulverización',
            self::VIA_RIEGO => 'Vía Riego',
            self::INYECCION => 'Inyección',
        };
    }
        public function getColor(): string
    {
        return match ($this) {
            self::TURBONEBULIZADOR => 'danger',
            self::TURBOCANON => 'success',
            self::HELICOPTERO => 'primary',
            self::DRON => 'warning',
            self::CARACOL => 'info',
            self::BOMBAESPALDA => 'secondary',
            self::BARRA => 'success',
            self::AZUFRADOR => 'dark',
            self::PITON => 'danger',
            self::BARRA_PULVERIZACION => 'success',
            self::VIA_RIEGO => 'primary',
            self::INYECCION => 'warning',
        };
    }
    public function getIcon(): ?string
    {
        return match ($this) {
            self::TURBONEBULIZADOR => 'heroicon-o-cloud',
            self::TURBOCANON => 'heroicon-o-fire',
            self::HELICOPTERO => 'heroicon-o-airplane',
            self::DRON => 'heroicon-o-drone',
            self::CARACOL => 'heroicon-o-bug',
            self::BOMBAESPALDA => 'heroicon-o-backpack',
            self::BARRA => 'heroicon-o-view-grid',
            self::AZUFRADOR => 'heroicon-o-sun',
            self::PITON => 'heroicon-o-cursor-click',
            self::BARRA_PULVERIZACION => 'heroicon-o-view-grid',
            self::VIA_RIEGO => 'heroicon-o-view-grid',
            self::INYECCION => 'heroicon-o-view-grid',
        };
    }

}
