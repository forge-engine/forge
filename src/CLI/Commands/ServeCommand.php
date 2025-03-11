<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;

class ServeCommand extends Command
{

    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Start the PHP Development Server';
    }

    public function execute(array $args): int
    {
        $host = $this->argument('host', $args) ?? 'localhost';
        $port = $this->argument('port', $args) ?? '8000';
        $publicDir = BASE_PATH . '/public';

        $this->info("Server running on http://$host:$port");
        passthru("php -S $host:$port -t $publicDir");
        return 0;
    }
}
