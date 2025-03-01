#!/usr/bin/env php
<?php
define("BASE_PATH", __DIR__);
define("CACHE_DIR", BASE_PATH . "/storage/framework/modules/cache");
require_once BASE_PATH . "/engine/Core/Bootstrap/Autoloader.php";

use Forge\Core\Bootstrap\Autoloader;
use Forge\Core\Helpers\App;
use Forge\Console\ConsoleKernel;
use Forge\Core\Bootstrap\Bootstrap;

Autoloader::initialize(BASE_PATH);

$appsRegistry = require_once BASE_PATH . '/apps.php';
App::registerAppNamespace($appsRegistry, BASE_PATH);

Bootstrap::init(BASE_PATH, true);
$bootstrap = Bootstrap::getBootstrap();

ConsoleKernel::init($bootstrap->getContainer());
$consoleKernel = ConsoleKernel::getConsoleKernel();
$status = $consoleKernel->handle($argv);

exit($status);