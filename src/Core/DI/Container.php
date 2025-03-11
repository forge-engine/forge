<?php

declare(strict_types=1);

namespace Forge\Core\DI;

use Closure;
use Forge\Core\DI\Attributes\Service;
use ReflectionClass;

final class Container
{
    private array $bindings = [];
    private array $services = [];
    private array $instances = [];
    private array $parameters = [];
    private array $tags = [];

    private static ?Container $instance = null;

    private function __construct() {}

    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @throws \ReflectionException
     */
    public function register(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Service::class);

        // Use Service attribute if present
        $serviceAttr = $attributes[0] ?? null;
        $id = $serviceAttr ? $serviceAttr->newInstance()->id : $class;

        $this->services[$id ?? $class] = [
            'class' => $class,
            'singleton' => $serviceAttr?->singleton ?? true
        ];
    }

    public function getServiceIds(): array
    {
        return array_keys($this->services);
    }

    public function bind(string $id, Closure|string $concrete, bool $singleton = false): void
    {
        $this->services[$id] = [
            'class' => $concrete,
            'singleton' => $singleton
        ];
    }

    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->services[$abstract] = [
            'class' => $concrete,
            'singleton' => true
        ];
    }

    public function tag(string $tag, array $abstracts): void
    {
        foreach ($abstracts as $abstract) {
            $this->tags[$tag][] = $abstract;
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function tagged(string $tag): array
    {
        return array_map(fn($abstract) => $this->make($abstract), $this->tags[$tag] ?? []);
    }

    public function setParameter(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function getParameter(string $key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @throws \ReflectionException
     */
    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $config = $this->services[$abstract] ?? null;

        if (!$config) {
            throw new \RuntimeException("Service $abstract not found");
        }

        $concrete = $config['class'];

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        if ($config['singleton']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * @throws \ReflectionException
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $config = $this->services[$id] ?? throw new \RuntimeException("Service $id not found");
        $instance = $this->make($id);

        if ($config['singleton']) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /** Build a class with dependencies
     * @throws \ReflectionException
     */
    private function build(string $class): object
    {
        $reflector = new ReflectionClass($class);

        if (!$constructor = $reflector->getConstructor()) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                throw new \RuntimeException("Cannot resolve parameter {$parameter->name} in {$class}");
            }

            if ($type instanceof \ReflectionNamedType) {
                $dependencies[] = $this->make($type->getName());
            } else {
                throw new \RuntimeException("Cannot resolve parameter {$parameter->name} in {$class}. Unsupported type.");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    private function __clone() {}

    /**
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
