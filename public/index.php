<?php

use Cubix\Foundation\Application;

define('DS', DIRECTORY_SEPARATOR);

define('DIR_ROOT', dirname(__DIR__));

require join(DS, [DIR_ROOT, 'vendor', 'autoload.php']);

new Application()
    ->boot();
