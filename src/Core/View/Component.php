<?php

declare(strict_types=1);

namespace Forge\Core\View;

use Attribute;
use Forge\Core\DI\Container;

#[Attribute(Attribute::TARGET_CLASS)]
class Component
{
    public function __construct(
        public string $name
    )
    {
    }

    /**
     * @throws \ReflectionException
     */
    public static function render(string $name, array $props = []): string
    {
        $componentClass = "App\\View\\Components\\" . ucfirst($name);

        if (!class_exists($componentClass)) {
            throw new \RuntimeException("Component {$name} not found");
        }

        $reflection = new \ReflectionClass($componentClass);
        $component = $reflection->newInstanceArgs([$props]);

        ob_start();
        $component->render();
        return ob_get_clean();
    }
}