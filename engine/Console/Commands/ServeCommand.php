<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class ServeCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Start development server';
    }

    public function execute(array $args): int
    {
        $host = $args[0] ?? '127.0.0.1';
        $port = $args[1] ?? '8080';
        $documentRoot = BASE_PATH . '/public';

        if (!is_dir($documentRoot)) {
            $this->error("Document root directory '{$documentRoot}' not found. Please create a 'public' directory or specify a different document root.");
            return 1;
        }

        passthru("php -S {$host}:{$port} -t {$documentRoot}");

        $this->info("Starting PHP development server...");
        $this->info("Listening on http://{$host}:{$port}");
        $this->info("Document root: {$documentRoot}");
        $this->comment("Press Ctrl+C to stop the server.");

        return 0;
    }
}