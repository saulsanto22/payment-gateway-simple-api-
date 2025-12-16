<?php

namespace App\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    public function __construct($message = 'Product not found.')
    {
        parent::__construct($message, 404);
    }
}
