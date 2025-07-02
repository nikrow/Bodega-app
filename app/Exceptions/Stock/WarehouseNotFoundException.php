<?php

namespace App\Exceptions\Stock;

use Exception;
use Throwable;
class WarehouseNotFoundException extends Exception
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "Bodega no encontrada.",int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

