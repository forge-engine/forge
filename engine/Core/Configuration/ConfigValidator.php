<?php

namespace Forge\Core\Configuration;

use RuntimeException;

class ConfigValidator
{
    /**
     * @param mixed $config
     * @param array<int,mixed> $schemas
     */
    public function validate($config, array $schemas): void
    {
        $configArray = $config->toArray();

        foreach ($schemas as $key => $schema) {
            $this->validateNode($key, $schema, $configArray);
        }
    }

    /**
     * @param array<int,mixed> $schema
     * @param array<int,mixed> $config
     */
    private function validateNode(string $key, array $schema, array $config, string $path = ''): void
    {
        $fullPath = $path ? "{$path}.{$key}" : $key;

        // Check required
        if (($schema['required'] ?? false) && !array_key_exists($key, $config)) {
            throw new RuntimeException("Missing required config key: {$fullPath}");
        }

        // Apply default
        if (!array_key_exists($key, $config) && isset($schema['default'])) {
            $config[$key] = $schema['default'];
        }

        // Type check
        $value = $config[$key] ?? null;

        if (isset($schema['type'])) {
            $this->checkType($fullPath, $value, $schema['type']);
        }


        // Custom validation
        if (isset($schema['validate'])) {
            $schema['validate']($value);
        }

        // Nested children
        if (isset($schema['children']) && is_array($value)) {
            foreach ($schema['children'] as $childKey => $childSchema) {
                $this->validateNode($childKey, $childSchema, $value, $fullPath);
            }
        }
    }

    /**
     * @param mixed $value
     */
    private function checkType(string $path, $value, string $type): void
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'int' => is_int($value),
            'bool' => is_bool($value),
            'array' => is_array($value) || is_object($value) && $value instanceof \ArrayObject,
            default => throw new RuntimeException("Unknown type: {$type}")
        };

        if (!$valid) {
            //throw new RuntimeException("Invalid type for {$path}: expected {$type}, got " . gettype($value));
        }
    }
}
