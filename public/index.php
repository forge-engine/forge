<?php

declare(strict_types=1);

define("BASE_PATH", dirname(__DIR__));

require BASE_PATH . "/engine/Core/Autoloader.php";

// Register autoloader
\Forge\Core\Autoloader::register();

// Check for maintenance mode
$maintenanceFile = BASE_PATH . '/storage/framework/maintenance.html';
if (file_exists($maintenanceFile)) {
    readfile($maintenanceFile);
    exit;
}

// Init Engine
\Forge\Core\Engine::init();
