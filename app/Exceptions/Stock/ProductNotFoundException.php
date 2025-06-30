<?php

namespace App\Exceptions\Stock;

use Exception;
use Throwable;

class ProductNotFoundException extends Exception
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "Producto no encontrado.",int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
