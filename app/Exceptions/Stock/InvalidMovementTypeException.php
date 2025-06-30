<?php

namespace App\Exceptions\Stock;

use Exception;
use Throwable;

class InvalidMovementTypeException extends Exception
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "Tipo de movimiento no válido.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
