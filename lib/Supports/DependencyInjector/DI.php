<?php

namespace Cubix\Supports\DependencyInjector;

use Cubix\Exception\Base\Invalid;
use Cubix\Exception\Base\Runtime;
use Cubix\Exception\Base\Type;
use Cubix\Supports\DependencyInjector\Exception\NotInstantiableClass;
use Cubix\Supports\DependencyInjector\Exception\UnresolvableDependency;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class DI
{
    /**
     * The class or object to be resolved.
     *
     * @var string|object
     */
    private string|object $class;

    /**
     * DependencyInjector constructor.
     *
     * @param null|string|object $class The class name or object to be resolved, or null if using a callback function
     */
    public function __construct(null|string|object $class = null)
    {
        $this->class = $class ?? '';
    }

    /**
     * Resolve and call a method on the given class.
     *
     * @param string $method The method name to resolve and call
     * @param array  $params User-provided parameters for the method
     *
     * @return mixed
     *
     * @throws Invalid If the method name is null or empty
     * @throws NotInstantiableClass     If class is not instantiable
     * @throws UnresolvableDependency
     */
    public function method(string $method, array $params = []): mixed
    {
        $this->cannotBeNull("Class", $this->class);

        $this->cannotBeNull("Method", $method);

        return $this->resolve("method", $method, $params);
    }

    /**
     * Resolve and call a callable.
     *
     * @param callable $callback The callable to be resolved and executed
     *
     * @return mixed
     */
    public function callback(callable $callback): mixed
    {
        return $this->resolve("callable", $callback);
    }

    /**
     * Resolve and instantiate the class constructor.
     *
     * @param null|string|object $class The class to instantiate, defaults to the class set in constructor
     *
     * @return object
     */
    public function get(null|string|object $class = null): object
    {
        return $this->resolve("constructor", $class ?? $this->class);
    }

    /**
     * Resolve dependencies based on type (constructor, method, or callable).
     *
     * @param string $type                      The type of resolution ("constructor", "method", "callable")
     * @param string|object|callable $resource  The resource to be resolved
     * @param array $params                     User-provided parameters
     *
     * @return mixed
     *
     * @throws NotInstantiableClass If the class is not instantiable
     * @throws Type If the type is unsupported
     * @throws UnresolvableDependency
     * @throws Runtime
     */
    private function resolve(string $type, string|object|callable $resource, array $params = []): mixed
    {
        try {
            if (strtolower($type) !== "callable") {
                $reflector = new ReflectionClass(
                    strtolower($type) === 'method'
                        ? $this->class
                        : $resource
                );

                if (!$reflector->isInstantiable()) {
                    throw new NotInstantiableClass(
                        class: is_object($this->class)
                            ? get_class($this->class)
                            : get_class($resource)
                    );
                }
            }

            return match (strtolower($type)) {
                'constructor' => $this->_construct(
                    $reflector,
                    $params
                ),
                'method' => $this->_invoke(
                    $reflector->getMethod($resource),
                    $params
                ),
                'callable' => $this->_call(
                    $resource,
                    $params
                ),
                default => throw new Type("Unsupported type: $type to resolve"),
            };
        } catch (ReflectionException $e) {
            throw new Runtime($e->getMessage());
        }
    }

    /**
     * Ensure that a resource is not null or empty.
     *
     * @param string $type    The type of resource (e.g., "Class" or "Method").
     * @param mixed $resource The resource to check.
     *
     * @throws Invalid If the resource is null or empty.
     */
    private function cannotBeNull(string $type, mixed $resource): void
    {
        if (empty($resource)) {
            throw new Invalid("{$type} cannot be null");
        }
    }

    /**
     * Resolve a class constructor and instantiate the class.
     *
     * @param ReflectionClass $reflector The reflection of the class to be instantiated.
     * @param array $params              User-provided parameters.
     *
     * @return object The instantiated class object.
     *
     * @throws UnresolvableDependency
     */
    private function _construct(ReflectionClass $reflector, array $params = []): object
    {
        $constructor = $reflector->getConstructor();
        $class = $reflector->getName();

        if ($constructor) {
            return new $class(...$this->_dependencies(
                $constructor->getParameters(),
                $params
            ));
        }

        return new $class();
    }

    /**
     * Resolve and call a method of a class.
     *
     * @param ReflectionMethod $reflector The reflection of the method to be called.
     * @param array $params               User-provided parameters.
     *
     * @return mixed The result of the method call.
     *
     * @throws ReflectionException If there is an error during reflection.
     * @throws UnresolvableDependency
     */
    private function _invoke(ReflectionMethod $reflector, array $params = []): mixed
    {
        $method = $reflector->getName();

        $instance = $this->_construct($reflector->getDeclaringClass());

        return $instance->{$method}(
            ...$this->_dependencies(
            $reflector->getParameters(),
            $params
        )
        );
    }

    /**
     * Resolve and call a callable.
     *
     * @param callable $callback The callable to resolve and call
     * @param array    $params   User-provided parameters
     *
     * @return mixed
     *
     * @throws ReflectionException
     * @throws UnresolvableDependency
     */
    private function _call(callable $callback, array $params = []): mixed
    {
        return $callback(...$this->_dependencies(
            new ReflectionFunction($callback)->getParameters(), $params
        ));
    }

    /**
     * Resolve dependencies for a list of parameters.
     *
     * @param ReflectionParameter[] $parameters An array of reflection parameters to resolve
     * @param array $explicitParams             User-provided parameters
     *
     * @return array
     *
     * @throws UnresolvableDependency
     */
    private function _dependencies(array $parameters, array $explicitParams = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (array_key_exists($name, $explicitParams)) {
                $dependencies[] = $explicitParams[$name];
                continue;
            }

            if ($type && !$type->isBuiltin()) {
                $dependencies[] = new self($type->getName())->get();
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new UnresolvableDependency("Cannot resolve dependency: \${$name}");
        }

        return $dependencies;
    }
}
