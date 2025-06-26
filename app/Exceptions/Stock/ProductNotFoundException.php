<?php

namespace App\Exceptions\Stock;

use Exception;
use Throwable;

class ProductNotFoundException extends Exception
{
    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de la excepción.
     * @param int $code Código de la excepción.
     * @param Throwable|null $previous Excepción previa.
     */
    public function __construct(string $message = "Producto no encontrado.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}