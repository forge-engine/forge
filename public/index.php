<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

define("BASE_PATH", dirname(__DIR__));

require_once BASE_PATH . "/engine/Core/Support/helpers.php";
require BASE_PATH . "/engine/Core/Autoloader.php";

\Forge\Core\Autoloader::register();

$maintenanceFile = BASE_PATH . '/storage/framework/maintenance.html';
if (file_exists($maintenanceFile)) {
    readfile($maintenanceFile);
    exit;
}

\Forge\Core\Engine::init();