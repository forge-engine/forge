<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'clear:log', description: 'Clear application logs')]
class ForgeLoggerCommand extends Command
{
    public function __construct(private readonly Config $config)
    {
    }
    public function execute(array $args): int
    {
        $path = $this->config->get('app.log.path', BASE_PATH . '/storage/logs/forge.log');

        if (file_exists($path)) {
            unlink($path);
            $this->success("Logs cleared successfully");
            return 0;
        }

        $this->info("No log file found");
        return 1;
    }
}
