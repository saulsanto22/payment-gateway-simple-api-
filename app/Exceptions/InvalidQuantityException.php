<?php

namespace App\Exceptions;

use Exception;

class InvalidQuantityException extends Exception
{
    public function __construct($message = 'Quantity must be greater than 0.')
    {
        parent::__construct($message, 422);
    }
}
