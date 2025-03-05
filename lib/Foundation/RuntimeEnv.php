<?php

namespace Cubix\Foundation;

class RuntimeEnv
{
    /**
     * Determines the current runtime environment (CLI or Web).
     *
     * @return string Returns 'cli' if running in command-line interface, 'web' otherwise
     */
    public static function env(): string
    {
        if (
            defined('STDIN')
            || php_sapi_name() == 'cli'
            || (stristr(PHP_SAPI, 'cgi') && getenv('TERM'))
            || (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0)
        ) {
            return 'cli';
        }

        return 'web';
    }

    /**
     * Checks if the current runtime environment matches the specified environment.
     *
     * @param string $env The environment to check against ('cli' or 'web')
     *
     * @return bool Returns true if the current environment matches the specified one, false otherwise
     */
    public static function envIs(string $env): bool
    {
        if (!in_array($env, ['cli', 'web'])) {
            return false;
        }

        return strtolower($env) === self::env();
    }

    /**
     * Retrieves information about the operating system.
     *
     * @param bool $short Whether to return only the OS name (true) or full details (false)
     *
     * @return string Returns the operating system name or full uname string
     */
    public static function os(bool $short = false): string
    {
        return php_uname($short ? 's' : 'a');
    }

    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Retrieves the memory usage of the current script.
     *
     * @param bool $real Whether to return real memory usage (true) or emalloc usage (false)
     *
     * @return int Returns the memory usage in bytes
     */
    public static function getMemoryUsage(bool $real = false): int
    {
        return memory_get_usage($real);
    }

    /**
     * Gets the current working directory of the script.
     *
     * @return string Returns the absolute path of the current working directory
     */
    public static function getWorkingDirectory(): string
    {
        return getcwd();
    }

    /**
     * Retrieves the current PHP version.
     *
     * @param bool $short Whether to return only the major.minor version (true) or full version (false)
     *
     * @return string Returns the PHP version (e.g., "8.2" or "8.2.3")
     */
    public static function getPHPVersion(bool $short = false): string
    {
        if ($short) {
            return implode('.', array_slice(explode('.', PHP_VERSION), 0, 2));
        }
        return PHP_VERSION;
    }
}
