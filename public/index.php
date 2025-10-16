<?php

declare(strict_types=1);

ini_set('memory_limit', '2G');

define("BASE_PATH", dirname(__DIR__));

require_once BASE_PATH . "/engine/Core/Support/helpers.php";
require BASE_PATH . "/engine/Core/Autoloader.php";

\Forge\Core\Autoloader::register();

$maintenanceFile = BASE_PATH . '/storage/framework/maintenance.html';
if (file_exists($maintenanceFile)) {
    readfile($maintenanceFile);
    exit;
}

try {
    \Forge\Core\Engine::init();
} catch (Throwable $e) {
    echo $e->getMessage();
}
