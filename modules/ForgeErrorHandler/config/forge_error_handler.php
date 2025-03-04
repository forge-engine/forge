<?php
return [
    'debugMode' => $_ENV['FORGE_APP_DEBUG'] === 'true',
    'logPath' => BASE_PATH . '/storage/logs/errors.log'
];