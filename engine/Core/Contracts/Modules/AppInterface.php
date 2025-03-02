<?php

namespace Forge\Core\Contracts\Modules;

use Forge\Core\DependencyInjection\Container;
use Forge\Http\Request;
use Forge\Http\Response;

abstract class AppInterface
{

    /**
     * Before applications is register and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    public function onBeforeAppRegister(Container $container): void
    {
        // Default implementation - do nothing. Apps can override if need it.
    }

    /**
     * Register module services and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    abstract public function register(Container $container): void;

    /**
     * After applications is register and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    public function onAfterAppRegister(Container $container): void
    {
        // Default implementation - do nothing. Apps can override if need it.
    }

    /**
     * Before applications boot and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    public function onBeforeBoot(Container $container): void
    {
        // Default implementation - do nothing. Apps can override if need it.
    }

    /**
     * Boot applications and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    abstract public function boot(Container $container): void;

    /**
     * After applications boot and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    public function onAfterBoot(Container $container): void
    {
        // Default implementation - do nothing. Apps can override if need it.
    }

    /**
     * Handle application requests.
     * This method MUST be implemented by all modules.
     *
     * @param Request $request
     * @return Response
     */
    abstract public function handleRequest(Request $request): Response;

    /**
     * Application commands
     * Applications can overrida this method if they need to.
     *
     * @param array<int,mixed> $args
     */
    public function handleCommand(array $args): int
    {
        // Default implementation - do nothing. Apps can override if need it.
        return 0;
    }
}
