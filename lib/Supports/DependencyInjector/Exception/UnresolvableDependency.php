<?php

namespace Cubix\Supports\DependencyInjector\Exception;

use Cubix\Exception\Base\Runtime;

class UnresolvableDependency extends Runtime
{
    public function __construct(string $dependency)
    {
        parent::__construct("Cannot resolve dependency {$dependency}");
    }
}
