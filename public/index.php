<?php

declare(strict_types=1);

define("BASE_PATH", dirname(__DIR__));

require BASE_PATH . "/engine/Core/Autoloader.php";

// Register autoloader
\Forge\Core\Autoloader::register();

// Init Engine
\Forge\Core\Engine::init();
