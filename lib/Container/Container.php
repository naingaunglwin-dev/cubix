<?php

namespace Cubix\Container;

use Cubix\Supports\DependencyInjector\DI;

class Container
{
    /**
     * Registered service definitions
     *
     * @var array<string, callable|string>
     */
    private array $definitions = [];

    /**
     * Singleton instances of services
     *
     * @var array<string, object>
     */
    private array $singletons = [];

    /**
     * Dependency Injector instance
     *
     * @var DI
     */
    private DI $di;

    /**
     * Singleton instance of the container
     *
     * @var Container|null
     */
    private static ?Container $instance;

    /**
     * Container constructor
     */
    public function __construct()
    {
        $this->di = new DI();
    }

    /**
     * Register a service definition
     *
     * @param string          $abstract   The name of the service
     * @param string|callable $definition The class name or a closure that defines the service
     * @return Container
     */
    public function add(string $abstract, string|callable $definition): Container
    {
        $this->definitions[$abstract] = $definition;
        return $this;
    }

    /**
     * Register a singleton service
     *
     * Ensures that the service is only instantiated once and the same instance
     * is returned on every subsequent request.
     *
     * @param string          $abstract   The name of the singleton service
     * @param string|callable $definition The class name or a closure that defines the service
     * @return Container
     */
    public function singleton(string $abstract, string|callable $definition): Container
    {
        $this->definitions[$abstract] = function () use ($abstract, $definition) {
            if (!isset($this->singletons[$abstract])) {
                $this->singletons[$abstract] = is_callable($definition)
                    ? $definition()
                    : $this->di->get($definition);
            }
            return $this->singletons[$abstract];
        };

        return $this;
    }

    /**
     * Resolve and instantiate a service
     *
     * @param string   $abstract   The name of the service
     * @param array    $parameters Optional parameters to pass to the service constructor
     *
     * @return object The instantiated service
     *
     * @throws \Exception If the service cannot be resolved
     */
    public function make(string $abstract, array $parameters = []): object
    {
        if (isset($this->definitions[$abstract])) {
            $definition = $this->definitions[$abstract];

            if (isset($this->singletons[$abstract])) {
                return $this->singletons[$abstract];
            }

            return is_callable($definition)
                ? $definition($this, ...$parameters)
                : $this->di->get($definition);
        }

        return $this->di->get($abstract);
    }

    /**
     * Check if a service is registered in the container
     *
     * @param string $abstract The name of the service
     *
     * @return bool True if the service exists, false otherwise
     */
    public function has(string $abstract): bool
    {
        return isset($this->definitions[$abstract]);
    }

    /**
     * The singleton instance of the container or the class that extend to container
     *
     * @param mixed ...$params Optional parameters for instantiation
     *
     * @return static The singleton instance of the container or the class that extend to container
     */
    public static function getInstance(...$params): static
    {
        if (empty(static::$instance)) {
            static::$instance = new static(...$params);
        }

        return static::$instance;
    }
}
