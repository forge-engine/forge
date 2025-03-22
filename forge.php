#!/usr/bin/env php
<?php
declare(strict_types=1);

define("BASE_PATH", __DIR__);

use Forge\Core\Bootstrap;
use Forge\Core\DI\Container;
use Forge\CLI\Application;
use Forge\Core\Autoloader;

require BASE_PATH . "/engine/Core/Autoloader.php";
Autoloader::register();

require BASE_PATH . "/engine/Core/Config/EnvParser.php";
Forge\Core\Config\EnvParser::load(BASE_PATH . "/.env");

$container = Container::getInstance();

// Setup container and register services
$container = Bootstrap::setupCliContainer();

$app = $container->get(Application::class);

exit($app->run($argv));

