<?php

namespace Forge\Modules\ForgeLogger;

use Forge\Core\Contracts\Modules\LoggerInterface;
use Forge\Core\Configuration\Config;

class FileLogger implements LoggerInterface
{
    private string $logPath;

    public function __construct(Config $config)
    {
        $this->logPath = BASE_PATH . '/' . $config->get('storage.log.path', 'storage/logs');

        if (!is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }
    }

    public function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logPath . '/app.log', $logMessage, FILE_APPEND);
    }
}
