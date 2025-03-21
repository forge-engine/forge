<?php

declare(strict_types=1);

if (preg_match('/\.env$/i', $_SERVER["REQUEST_URI"])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

define("BASE_PATH", dirname(__DIR__));

require BASE_PATH . "/engine/Core/Autoloader.php";
require_once BASE_PATH . "/engine/Core/Support/helpers.php";

use Forge\Core\Autoloader;
use Forge\Core\Bootstrap;
use Forge\Core\Http\Request;

Autoloader::register();

$kernel = Bootstrap::init();
$response = $kernel->handler(Request::createFromGlobals());
$response->send();
