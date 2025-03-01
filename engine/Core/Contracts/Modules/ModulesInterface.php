<?php

namespace Forge\Core\Contracts\Modules;

use Forge\Core\DependencyInjection\Container;

abstract class ModulesInterface
{
    /**
     * Register module services and bindings in the container.
     * This method MUST be implemented by all modules.
     *
     * @param Container $container
     * @return void
     */
    abstract public function register(Container $container): void;

    /**
     * Actiosn to perform before the module boots (Optional).
     * Modules can override this method if they need to run code before boot.
     *
     * @param Container $container
     * @return void
     */
    public function onBeforeBoot(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }

    /**
     * Actions to perform after the module boots (Optional).
     * Modules can override this methods if they need to run code after boot.
     *
     * @param Container $container
     * @return void
     */
    public function onAfterBoot(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }

    /**
     * Actions to perfom after configuration is loaded (Optional).
     * Modules can override this method if they need to act on loaded config.
     *
     * @param Container $container
     * @return void
     */
    public function onAfterConfigLoaded(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }

    /**
     * Actions to perfom before module is loaded (Optional).
     * Modules can override this method if they need to act on loaded config.
     *
     * @param Container $container
     * @return void
     */
    public function onBeforeModuleLoad(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }

    /**
     * Actions to perfom after module is loaded (Optional).
     * Modules can override this method if they need to act on loaded config.
     *
     * @param Container $container
     * @return void
     */
    public function onAfterModuleLoad(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }

    /**
     * Actions to perfom before module is register (Optional).
     * Modules can override this method if they need to act on loaded config.
     *
     * @param Container $container
     * @return void
     */
    public function onBeforeModuleRegister(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }

    /**
     * Actions to perfom after module is register (Optional).
     * Modules can override this method if they need to act on loaded config.
     *
     * @param Container $container
     * @return void
     */
    public function onAfterModuleRegister(Container $container): void
    {
        // Default implementation - do nothing. Modules can override if need it.
    }
}