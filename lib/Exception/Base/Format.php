<?php

namespace Cubix\Exception\Base;

use InvalidArgumentException;

/**
 * Base Format Exception class
 */
class Format extends InvalidArgumentException
{
    use CubixException;
}
