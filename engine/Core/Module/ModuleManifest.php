<?php

namespace Forge\Core\Module;

use RuntimeException;

class ModuleManifest
{
    private array $data;
    public string $manifestPath;

    public function __construct(string $manifestPath)
    {
        if (!file_exists($manifestPath)) {
            throw new RuntimeException("Module manifest not found: {$manifestPath}");
        }

        $this->data = json_decode(file_get_contents($manifestPath), true);
        $this->manifestPath = $manifestPath;

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in manifest: " . json_last_error_msg());
        }

        $this->validate();
    }

    public function getOrder(): int
    {
        return $this->data['order'] ?? PHP_INT_MAX;
    }

    private function validate(): void
    {
        // Required fields
        $required = ['name', 'version', 'provides', 'class', 'compatibility'];
        foreach ($required as $field) {
            if (!isset($this->data[$field])) {
                throw new RuntimeException("Missing required field: {$field}");
            }
        }

        // Validate version format (semver)
        if (!preg_match('/^\d+\.\d+\.\d+$/', $this->data['version'])) {
            throw new RuntimeException("Version must be semantic (X.Y.Z)");
        }

        // Validate "provides" and "requires" entries
        foreach (['provides', 'requires'] as $field) {
            if (isset($this->data[$field])) {
                foreach ($this->data[$field] as $entry) {
                    if (!str_contains($entry, '@')) {
                        throw new RuntimeException("{$field} entry '{$entry}' must include a version (e.g., DatabaseInterface@1.0)");
                    }
                }
            }
        }
    }

    // Required fields
    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getCore(): bool
    {
        return $this->data['core'] ?? false;
    }

    public function getVersion(): string
    {
        return $this->data['version'];
    }

    public function getProvides(): array
    {
        return $this->data['provides'];
    }

    public function getClass(): string
    {
        return $this->data['class'];
    }

    // Optional fields with defaults
    public function getDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    public function getType(): string
    {
        return $this->data['type'] ?? 'generic';
    }

    public function getRequires(): array
    {
        return $this->data['requires'] ?? [];
    }

    public function getLifecycleHooks(): array
    {
        return $this->data['lifecycleHooks'] ?? [];
    }

    public function getLifecycle(string $name): string|null
    {
        $normalizeLifecycle = strtolower($name);
        foreach ($this - data['lifecycleHooks'] as $lifeIndex => $lifecycle) {
            if (strtolower($lifeIndex) === $normalizeLifecycle) {
                return $lifecycle;
            }
        }
        return null;
    }

    public function getCli(): array
    {
        return $this->data['cli'] ?? ['commands' => [], 'helpers' => []];
    }

    public function getPaths(): array
    {
        return $this->data['paths'] ?? ['src' => 'src/'];
    }

    public function getTags(): array
    {
        return $this->data['tags'] ?? [];
    }

    public function getConfigDefaults(): array
    {
        return $this->data['config']['defaults'] ?? [];
    }

    // Helper methods
    public function providesInterface(string $interface): bool
    {
        foreach ($this->getProvides() as $provided) {
            [$providedInterface, $version] = explode('@', $provided);
            if ($providedInterface === $interface) {
                return true;
            }
        }
        return false;
    }

    public function getCompatibility(): array
    {
        return $this->data['compatibility'] ?? [];
    }

    /**
     * Get the PHP version compatibility requirement from the manifest.
     *
     * @return string|null Returns the PHP version requirement string (e.g., "^8.1") or null if not defined.
     */
    public function getPhpCompatibility(): ?string
    {
        return $this->getCompatibility()['php'] ?? null;
    }

    /**
     * Get the Framework version compatibility requirement from the manifest.
     *
     * @return string|null Returns the Framework version requirement string (e.g., ">=1.0.0") or null if not defined.
     */
    public function getFrameworkCompatibility(): ?string
    {
        return $this->getCompatibility()['framework'] ?? null;
    }

    public function getRepository(): array
    {
        return $this->data['repository'] ?? [];
    }
}
