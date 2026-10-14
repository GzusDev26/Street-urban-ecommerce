<?php

namespace App\Exceptions;

use RuntimeException;

class CartStockException extends RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('El stock del carrito cambió. Revisa las cantidades antes de confirmar.');
    }
}
