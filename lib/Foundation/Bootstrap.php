<?php

namespace Cubix\Foundation;

use Cubix\Exception\Base\Missing;
use Cubix\Supports\DependencyInjector\DI;

class Bootstrap
{
    /**
     * Array of exception handling callables.
     *
     * @var array
     */
    private array $exceptions = [];

    /**
     * Configuration for autoloading files.
     *
     * @var array
     */
    private array $autoload = [];

    /**
     * Array of callables to execute before application export.
     *
     * @var array
     */
    private array $before = [];

    /**
     * Registers an exception handling callable.
     *
     * @param callable $e The exception handling callable to register
     *
     * @return Bootstrap Returns the current instance for method chaining
     */
    public function exception(callable $e): Bootstrap
    {
        $this->exceptions[] = $e;

        return $this;
    }

    /**
     * Configures autoloading of files.
     *
     * @param string|array $file   The file(s) to autoload, can be a string or array of strings
     * @param bool         $strict Whether to throw an exception if a file is missing (true) or skip it (false)
     *
     * @return Bootstrap Returns the current instance for method chaining
     */
    public function autoload(string|array $file, bool $strict = false): Bootstrap
    {
        $this->autoload = [
            'strict' => $strict,
            'file' => is_array($file) ? $file : [$file],
        ];

        return $this;
    }

    /**
     * Registers a callable to execute before application export.
     *
     * @param callable $callable The callable to execute before export
     *
     * @return Bootstrap Returns the current instance for method chaining
     */
    public function before(callable $callable): Bootstrap
    {
        $this->before[] = $callable;

        return $this;
    }

    /**
     * Exports the configured application instance.
     *
     * @return Application Returns the configured Application instance
     */
    public function export(): Application
    {
        $app = Application::getInstance();

        $this->_exception($app);

        $this->_before();

        $this->_autoload();

        return $app;
    }

    /**
     * Registers exception handlers with the application.
     *
     * @param Application $app The application instance to register exceptions with
     */
    private function _exception(Application $app): void
    {
        if (!empty($this->exceptions)) {
            foreach ($this->exceptions as $e) {
                // TODO: register to framework's exception handler
            }
        }
    }

    /**
     * Handles the autoloading of configured files.
     *
     * @throws Missing If a file does not exist and strict mode is enabled
     */
    private function _autoload(): void
    {
        if (empty($this->autoload)) {
            return;
        }

        $strict = $this->autoload['strict'];
        $files = $files = array_merge(...array_map(
            fn($file) => is_array($file) ? $file : [$file],
            $this->autoload['file']
        ));

        foreach ($files as $file) {
            if (is_file($file)) {
                require $file;
            } elseif ($strict) {
                throw new Missing("$file does not exist");
            }
        }
    }

    /**
     * Executes all registered before callables.
     */
    private function _before(): void
    {
        foreach ($this->before as $callback) {
            new DI()->callback($callback);
        }
    }
}
