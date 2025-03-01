<?php

namespace Forge\Core\Contracts\Modules;

use Forge\Core\DependencyInjection\Container;
use Forge\Http\Response;

interface DebugBarInterface
{
    /**
     * Add Collector
     *
     * @param string $name
     * @param callable $collector
     *
     * @return void
     */
    public function addCollector(string $name, callable $collector): void;

    /**
     * Get data information such as memory, time, database etc.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Render the debugbar to the user
     *
     * @return string
     */
    public function render(): string;

    public function injectDebugBarIfEnabled(Response $response, Container $container): Response;

}