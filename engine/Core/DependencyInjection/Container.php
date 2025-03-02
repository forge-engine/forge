<?php

namespace Forge\Core\DependencyInjection;

use ReflectionClass;
use ReflectionParameter;

class Container
{
    private static ?Container $instance = null;
    private array $bindings = [];
    private array $instances = [];
    private array $contextual = [];
    private array $resolvingStack = [];
    private array $tags = [];

    public static function init(): Container
    {
        if (self::$instance === null) {
            self::$instance = new Container();
        }
        return self::$instance;
    }

    public static function getContainer(): Container
    {
        if (self::$instance === null) {
            throw new \RuntimeException("Container not initialized. Call Container::init() first.");
        }
        return self::$instance;
    }

    public function bind(string $abstract, ?string $concrete = null, bool $singleton = false): self
    {
        if ($this->has($abstract)) {
            return $this;
        }
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => $singleton
        ];
        return $this;
    }

    public function singleton(string $abstract, ?string $concrete = null): self
    {
        if ($this->has($abstract)) {
            return $this;
        }
        return $this->bind($abstract, $concrete, true);
    }

    public function when(string $parent): ContextualBinding
    {
        return new ContextualBinding($this, $parent);
    }

    /**
     * @param array<int,mixed> $parameters
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (in_array($abstract, $this->resolvingStack)) {
            throw new ContainerException("Circular dependency detected: " . implode(' -> ', $this->resolvingStack));
        }
        $this->resolvingStack[] = $abstract;

        $concrete = $this->getConcrete($abstract);

        $object = $this->build($concrete, $parameters);

        // Handle singletons
        if ($this->isSingleton($abstract)) {
            $this->instances[$abstract] = $object;
        }

        array_pop($this->resolvingStack);

        return $object;
    }

    /**
     * Resolve the given abstract from the container.
     *
     * @template T
     * @param string $abstract
     * @return T
     * @throws ContainerException
     */
    public function get(string $abstract): mixed
    {
        $instance = $this->make($abstract);
        return $instance;
    }

    public function instance(string $abstract, mixed $instance): self
    {
        if ($this->has($abstract)) {
            return $this;
        }
        if ($instance instanceof \Closure) {
            $this->instances[$abstract] = $instance($this);
        } else {
            $this->instances[$abstract] = $instance;
        }
        return $this;
    }

    public function addContextualBinding(string $parent, string $abstract, string $concrete): void
    {
        $this->contextual[$parent][$abstract] = $concrete;
    }

    private function getConcrete(string $abstract): string
    {
        if (!empty($this->contextual)) {
            $parent = end($this->resolvingStack);
            if ($parent && isset($this->contextual[$parent][$abstract])) {
                return $this->contextual[$parent][$abstract];
            }
        }

        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

    /**
     * @param array<int,mixed> $parameters
     */
    private function build(string $concrete, array $parameters = []): mixed
    {
        if (!class_exists($concrete)) {
            throw new ContainerException("Class {$concrete} does not exist.");
        }

        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            $instance = new $concrete();
        } else {
            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
                $dependencies[] = $this->resolveParameter($param, $parameters);
            }
            try {
                $instance = $reflector->newInstanceArgs($dependencies);
            } catch (\ReflectionException $e) {
                throw new ContainerException("Failed to instantiate {$concrete} due to constructor error: " . $e->getMessage(), 0, $e);
            }
        }

        // Inject properties with @inject
        $this->injectProperties($instance);

        return $instance;
    }

    /**
     * @param array<int,mixed> $customParams
     */
    private function resolveParameter(ReflectionParameter $param, array $customParams): mixed
    {
        $paramName = $param->getName();
        $paramType = $param->getType();

        if (array_key_exists($paramName, $customParams)) {
            return $customParams[$paramName];
        }

        if ($paramType && !$paramType->isBuiltin()) {
            $paramType = $paramType instanceof \ReflectionNamedType ? $paramType->getName() : null;
            return $this->make($paramType);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new ContainerException("Unresolvable parameter: \${$paramName} in {$param->getDeclaringClass()->getName()}");
    }

    // Inject properties marked with @inject
    private function injectProperties(object $instance): void
    {
        $reflector = new ReflectionClass($instance);
        foreach ($reflector->getProperties() as $property) {
            $docComment = $property->getDocComment();
            $trimmedDocComment = trim($docComment);
            if (strpos($trimmedDocComment, '@inject') !== false) {
                $propertyType = $property->getType()->getName();
                $property->setAccessible(true);
                $property->setValue($instance, $this->make($propertyType));
            }
        }
    }

    private function isSingleton(string $abstract): bool
    {
        return $this->bindings[$abstract]['singleton'] ?? false;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * @param array<int,mixed> $tags
     */
    public function tag(string $abstract, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            $this->tags[$tag][] = $abstract;
        }
    }

    /**
     * @return array Retunr array of class names
     */
    public function getTagged(string $tag): array
    {
        return array_map(fn($abstract) => $this->make($abstract), $this->tags[$tag] ?? []);
    }
}

class ContextualBinding
{
    private string $abstract;

    public function __construct(
        private Container $container,
        private string    $parent
    )
    {
    }

    public function needs(string $abstract): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function give(string $concrete): void
    {
        $this->container->addContextualBinding($this->parent, $this->abstract, $concrete);
    }
}

// Custom exception
class ContainerException extends \RuntimeException
{
}
