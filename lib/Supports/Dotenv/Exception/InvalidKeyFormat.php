<?php

namespace Cubix\Supports\Dotenv\Exception;

use Cubix\Exception\Base\Format;

class InvalidKeyFormat extends Format
{
    public function __construct($key)
    {
        parent::__construct("The key '{$key}' is not a valid format.");
    }
}
