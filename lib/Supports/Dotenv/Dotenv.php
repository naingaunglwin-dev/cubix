<?php

namespace Cubix\Supports\Dotenv;

use Cubix\Supports\Dotenv\Exception\InvalidKeyFormat;
use Cubix\Supports\Dotenv\Exception\MissingEnvFile;

class Dotenv
{
    /**
     * The array of environment files to be loaded.
     *
     * @var array
     */
    private array $files;

    /**
     * The local environment variables loaded from files.
     *
     * @var array
     */
    private array $local = [];

    /**
     * The global environment variables.
     *
     * @var array
     */
    private array $global = [];

    /**
     * Whether the environment variables have been loaded.
     *
     * @var bool
     */
    private bool $loaded = false;

    /**
     * Grouped environment variables.
     *
     * @var array
     */
    private array $group = [];

    /**
     * The keys of all loaded environment variables.
     *
     * @var array
     */
    private array $keys = [];

    /**
     * The default environment file names to look for.
     *
     * @var array
     */
    private array $defaults = [
        '.env', '.env.local',
        '.env.development', '.env.production',
        '.env.dev', '.env.prod'
    ];

    /**
     * Key for storing environment variable keys in $_SERVER.
     *
     * @var string
     */
    private const KEY_HOLDER = "_cubix_env_keys";

    /**
     * Dotenv constructor.
     *
     * Initializes the Dotenv instance by loading environment variables
     * from specified files or default files if none are provided.
     *
     * @param string|array<string>|null $file The environment file(s) to load.
     */
    public function __construct(string|array|null $file = null)
    {
        if (!$file) {
            $file = $this->find();
        } else {
            $this->validate($file);
        }

        $this->files = $file;

        $this->load($this->files);
    }

    /**
     * Get an environment variable value.
     *
     * Returns the value of the specified environment variable key.
     * If no key is specified, returns all loaded environment variables.
     *
     * @param string|null $key The environment variable key.
     * @param mixed $default   The default value if the key is not found.
     *
     * @return mixed The value of the environment variable or the default value.
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        if (!$this->loaded) $this->load($this->files);

        if (is_null($key)) {
            return $this->global;
        }

        return $this->global[$key] ?? $default;
    }

    /**
     * Get a group of environment variables.
     *
     * Returns all environment variables that belong to the specified group.
     *
     *       [
     *         'APP' => [
     *           'APP_NAME'   => 'cubix'
     *           'APP_ENV'    => 'development',
     *           'APP_LOCALE' => 'en'
     *         ]
     *       ]
     *
     * @param string $group  The group name.
     * @param mixed $default The default value if the group is not found.
     *
     * @return mixed The group of environment variables or the default value.
     */
    public function group(string $group, mixed $default = []): mixed
    {
        if (!$this->loaded) $this->load($this->files);

        return $this->group[$group] ?? $default;
    }

    /**
     * Find available environment files.
     *
     * Searches the default environment files in the project root directory.
     *
     * @return array The list of available environment files.
     */
    private function find(): array
    {
        return array_filter($this->defaults, fn($file) => file_exists(DIR_ROOT . DS . $file));
    }

    /**
     * Load environment variables from files.
     *
     * Loads environment variables from the specified file(s) and stores them
     * in the local and global arrays. If variables are already loaded, this method returns early.
     *
     * @param string|array|null $files The environment file(s) to load.
     *
     * @return Dotenv Returns the current Dotenv instance.
     */
    public function load(string|array|null $files = null): Dotenv
    {
        if ($this->loaded) {
            return $this;
        }

        if (!empty($files)) {
            $this->validate($files);
        }

        $files = array_merge($this->files, is_string($files) ? [$files] : $files);

        $data = [];

        foreach ($files as $f) {
            $path = DIR_ROOT . "/$f";
            if (!file_exists($path)) {
                continue;
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                continue;
            }

            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2) + [1 => ''];
                $key   = $this->trim($key);
                $value = $this->trim($value);

                if ($key !== '') {
                    $data[$key] = $value;
                    if (str_contains($key, '_')) {
                        $group = strtok($key, '_');
                        $this->group[$group][$key] = $value;
                    }
                }
            }

            fclose($handle);
        }

        $data['REQUEST_SCHEME'] = $_SERVER['REQUEST_SCHEME'] ?? 'http';

        $this->store($data);

        $this->loaded = true;

        $this->update();

        return $this;
    }

    /**
     * Reloads environment variables from the specified files.
     *
     * Resets the local environment variables, reloads the environment
     * variables from the files, and updates the global environment variables. If a
     * default set of variables is provided, they are merged with the reloaded
     * variables.
     *
     * @param array|null $default An optional array of default environment variables to merge after reloading
     *
     * @return Dotenv
     */
    public function reload(?array $default = null): Dotenv
    {
        $this->local = [];

        $this->update();

        $this->load($this->files);

        $this->update($default);

        return $this;
    }

    /**
     * Update the environment variables.
     *
     * Updates the local and global environment variables and synchronizes them with
     * the PHP `$_SERVER` and `$_ENV` superglobals.
     *
     * @param array|null $default The default environment variables to merge.
     *
     * @return void
     */
    private function update(?array $default = null): void
    {
        $previousKeys = isset($_SERVER[self::KEY_HOLDER])
            ? explode(',', $_SERVER[self::KEY_HOLDER])
            : $this->keys;

        if (!empty($default)) {
            $this->local = array_merge($this->local, $default);
        }

        if (!empty($this->local)) {
            $this->match($this->local);

            $keys = [];

            foreach ($this->local as $key => $value) {
                putenv(sprintf('%s=%s', $key, $value));
                $keys[] = $key;
            }

            $this->keys = $keys;

            $diff = array_diff_key($previousKeys, $this->keys);

            if (!empty($diff)) {
                foreach ($diff as $key => $value) {
                    $this->unset($key);
                }
            }

            $this->global = $_ENV = $this->local;

            $_SERVER += $this->global;
            $_SERVER[self::KEY_HOLDER] = implode(',', $this->keys);
        }
    }

    /**
     * Unset an environment variable.
     *
     * Removes the specified environment variable from `$_SERVER`, `$_ENV`, and the process environment.
     *
     * @param string $key The environment variable key to unset.
     *
     * @return void
     */
    private function unset(string $key): void
    {
        if (isset($_SERVER[$key])) unset($_SERVER[$key]);
        putenv($key);
        if (isset($_ENV[$key])) unset($_ENV[$key]);
    }

    /**
     * Store environment variables.
     *
     * Stores the environment variables in the local array after matching the keys against a pattern.
     *
     * @param array $envs The environment variables to store.
     *
     * @return void
     */
    private function store(array $envs): void
    {
        foreach ($envs as $key => $value) {
            $this->match([$key => $value]);
            $this->local[$key] = $value;
        }
    }

    /**
     * Validate environment variable keys.
     *
     * Ensures that all environment variable keys match the allowed pattern.
     *
     * @param array $vars The array of environment variables to validate.
     *
     * @return void
     *
     * @throws InvalidKeyFormat If a variable key has an invalid format.
     */
    private function match(array $vars): void
    {
        $pattern = '/^[a-zA-Z_][a-zA-Z_.]*$/';
        foreach ($vars as $key => $value) {
            if (!preg_match($pattern, $key)) throw new InvalidKeyFormat($pattern);
        }
    }

    /**
     * Trim whitespace and unwanted characters from a string.
     *
     * Removes leading and trailing whitespace, reduces internal whitespace to a single space, single quote and double quote
     *
     * @param string $string The string to trim.
     *
     * @return string The trimmed string.
     */
    private function trim(string $string): string
    {
        return preg_replace('/\s+/', '', trim($string, "\"'"));
    }

    /**
     * Validates the existence of the specified environment files.
     *
     * Checks whether the given environment files exist in the project directory.
     * If any of the files do not exist, a `MissingEnvFile` exception is thrown.
     *
     * @param string|array<string> $file A single environment file or an array of environment file names to validate.
     *
     * @return void
     *
     * @throws MissingEnvFile If any of the specified files do not exist.
     */
    private function validate(array|string $file): void
    {
        $files = is_string($file) ? [$file] : $file;

        foreach ($files as $file) {
            $path = DIR_ROOT . "/$file";
            if (!file_exists($path)) throw new MissingEnvFile($path);
        }
    }
}
