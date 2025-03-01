<?php

namespace Forge\Http;

class ParameterBag
{
    /**
     * @var array<array-key, mixed>
     */
    private array $parameters;

    /**
     * ParameterBag constructor.
     *
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Get all parameters.
     *
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Get a parameter by key.
     *
     * @param string $key
     * @param mixed|null $default Default value to return if key is not found.
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Check if a parameter exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }
}