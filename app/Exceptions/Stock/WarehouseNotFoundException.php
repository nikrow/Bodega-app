<?php

namespace App\Exceptions\Stock;

use Exception;

class WarehouseNotFoundException extends Exception
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "Bodega no encontrada.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

