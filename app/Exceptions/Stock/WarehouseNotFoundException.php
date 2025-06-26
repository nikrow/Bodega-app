<?php

namespace App\Exceptions\Stock;

use Exception;
use Throwable;

class WarehouseNotFoundException extends Exception
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de la excepción.
     * @param int $code Código de la excepción.
     * @param Throwable|null $previous Excepción previa.
     */
    public function __construct(string $message = "Bodega no encontrada.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}