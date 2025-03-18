<?php

namespace App\Enums;

enum Destination: string
{
    case RECICLAJE_CAMPO_LIMPIO = 'reciclaje_campo_limpio';
    case RECICLAJE_VIVE_VERDE = 'reciclaje_vive_verde';
    case RAICES = 'raices';
    case PROVEEDOR = 'proveedor';
    case OTROS = 'otros';

    /**
     * Obtener las opciones para el formulario.
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::RECICLAJE_CAMPO_LIMPIO->value => 'Reciclaje Campo Limpio',
            self::RECICLAJE_VIVE_VERDE->value => 'Reciclaje Vive Verde',
            self::RAICES->value => 'Raíces',
            self::PROVEEDOR->value => 'Proveedor',
            self::OTROS->value => 'Otros',
        ];
    }
}
