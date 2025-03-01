<?php
if (PHP_VERSION_ID < 80200) {
    echo "Error: Forge requires PHP 8.2 or higher. Your current PHP version is " . PHP_VERSION . "\n";
    exit(1);
}

// --- Define Core Paths ---
define("BASE_PATH", dirname(__DIR__));

// --- Initialize Autoloader ---
require_once BASE_PATH . "/engine/Core/Bootstrap/Autoloader.php";

use Forge\Core\Bootstrap\Autoloader;

Autoloader::initialize(BASE_PATH);

// --- Initialize Framework with SimpleBootstrap -- Manually bootstrap available ---
use \Forge\Core\Bootstrap\SimpleBootstrap;

SimpleBootstrap::run();