<?php

namespace Cubix\Foundation;

class Application
{
    const string VERSION = '1.0.0';

    public function __construct()
    {
    }

    public function boot()
    {
        echo "Running App";
    }

    public function version()
    {
        return self::VERSION;
    }
}
