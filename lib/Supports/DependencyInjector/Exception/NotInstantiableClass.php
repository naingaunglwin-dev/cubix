<?php

namespace Cubix\Supports\DependencyInjector\Exception;

use Cubix\Exception\Base\Runtime;

class NotInstantiableClass extends Runtime
{
    public function __construct(string $class)
    {
        parent::__construct("{$class} is not instantiable");
    }
}