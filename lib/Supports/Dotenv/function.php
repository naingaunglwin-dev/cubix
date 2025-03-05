<?php

if (!function_exists('env')) {
    /**
     * Gets an environment variable value from .env files
     *
     * @param string $key         Environment variable key
     * @param mixed|null $default Default value if key not found
     *
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return new \Cubix\Supports\Dotenv\Dotenv()->get($key, $default);
    }
}
