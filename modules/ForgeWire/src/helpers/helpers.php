<?php

use App\Modules\ForgeWire\Core\WireComponent;
use App\Modules\ForgeWire\Support\Renderer;
use App\Modules\ForgeWire\Core\Hydrator;

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
        mixed $props = null,
        mixed $componentId = null,
    ): string {
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
        mixed $props = null,
        mixed $componentId = null,
    ): string {
        return wire($componentClass, $props, $componentId);
    }
}

if (!function_exists("wire_name") && function_exists("wire")) {
    /**
     * Name-based resolver (optional):
     *   wire_name('products-table', ['perPage'=>5])
     * => App\Components\ProductsTable
     */
    function wire_name(
        string $name,
        mixed $props = null,
        mixed $componentId = null,
    ): string {
        $pascal = str_replace(
            " ",
            "",
            ucwords(str_replace(["-", "_", "."], " ", $name)),
        );
        $class = "App\\Components\\{$pascal}";
        return wire($class, $props, $componentId);
    }
}