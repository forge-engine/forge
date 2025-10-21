<?php

use App\Modules\ForgeWire\Core\WireComponent;
use App\Modules\ForgeWire\Support\Renderer;
use App\Modules\ForgeWire\Core\Hydrator;
use Forge\Core\Helpers\Strings;

if (!function_exists("forgewire_is_available")) {
    function forgewire_is_available(): bool
    {
        return class_exists(Renderer::class) &&
            class_exists(WireComponent::class);
    }
}

if (!function_exists("wire")) {
    /**
     * Render a ForgeWire component.
     *
     */
    function wire(
        string $componentClass,
        mixed  $props = null,
        mixed  $componentId = null,
    ): string
    {
        return Hydrator::wire($componentClass, $props, $componentId);
    }
}

if (!function_exists("w") && function_exists("wire")) {
    /**
     * Alias: shorter name for Forge wire component
     *   w(ProductsTable::class, ['perPage' => 5])
     *   w(ProductsTable::class, 'products-1')
     */
    function w(
        string $componentClass,
        mixed  $props = null,
        mixed  $componentId = null,
    ): string
    {
        return wire($componentClass, $props, $componentId);
    }
}


if (!function_exists('wire_name') && function_exists('wire')) {
    /**
     * Convert ke-bab / snake / dot name to a Wire component class
     * and render it.
     *
     * products-table   -> App\Components\Wire\ProductsTable
     * user.form        -> App\Components\Wire\UserForm
     */
    function wire_name(
        string $name,
        mixed  $props = null,
        mixed  $componentId = null,
    ): string
    {
        if (str_contains($name, ':')) {
            [$module, $tail] = explode(':', $name, 2);
            $parts = array_map(
                fn($s) => str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $s))),
                explode('.', $tail)
            );
            $class = 'App\\Modules\\' . Strings::toPascalCase($module)
                . '\\Resources\\Components\\Wire\\' . implode('\\', $parts);
        } else {
            $parts = array_map(
                fn($s) => str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $s))),
                explode('.', $name)
            );
            $class = 'App\\Components\\Wire\\' . implode('\\', $parts);
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Wire component class not found: {$class}");
        }

        return wire($class, $props, $componentId);
    }
}