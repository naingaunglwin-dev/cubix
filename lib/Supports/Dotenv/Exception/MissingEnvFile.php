<?php

namespace Cubix\Supports\Dotenv\Exception;

use Cubix\Exception\Base\Missing;

class MissingEnvFile extends Missing
{
    public function __construct($file)
    {
        parent::__construct("$file does not exist");
    }
}
