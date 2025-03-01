<?php

namespace Forge\Core\Bootstrap;

use Forge\Core\Helpers\App;
use Forge\Core\Bootstrap\Bootstrap;
use Forge\Http\Request;

class SimpleBootstrap
{
    public static function run(): void
    {
        // --- Define Core Paths ---
        define("CACHE_DIR", BASE_PATH . "/storage/framework/modules/cache");

        $appsRegistry = require_once BASE_PATH . '/apps.php';
        App::registerAppNamespace($appsRegistry, BASE_PATH);

        // --- Initialize Application Bootstrap Class ---
        Bootstrap::init(BASE_PATH);
        $bootstrap = Bootstrap::getBootstrap();

        // --- Retrieve Container and App Manager ---
        $container = $bootstrap->getContainer();
        $appManager = $bootstrap->getAppManager();

        // --- Create Request Object from Globals ---
        $request = Request::createFromGlobals();

        // --- Trigger 'beforeRequest' Application Event ---
        $appManager->trigger('beforeRequest', $container, $request);

        // --- Handle Request and Get Response ---
        $response = $bootstrap->handleRequest($request);

        // --- Trigger 'afterRequest' Application Event ---
        $appManager->trigger('afterRequest', $container, $request, $response);

        // --- Send Response to Client ---
        $response->send();

    }
}