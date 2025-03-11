<?php

declare(strict_types=1);

namespace Forge\Core\Routing;

use Forge\Core\DI\Container;
use ReflectionClass;

final class ControllerLoader
{
    public function __construct(
        private Container $container,
        private string    $controllerDir
    )
    {
    }

    /** Auto-register controllers from directory
     * @throws \ReflectionException
     */
    public function registerControllers(): void
    {
        foreach (glob("$this->controllerDir/*.php") as $file) {
            $class = $this->fileToClass($file);
            $this->container->register($class);
        }
    }

    private function fileToClass(string $file): string
    {
        $basePath = BASE_PATH . '/app/Controllers/';
        $className = str_replace(
            [$basePath, '.php', '/'],
            ['', '', '\\'],
            $file
        );
        return 'App\\Controllers\\' . $className;
    }
}