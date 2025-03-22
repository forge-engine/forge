<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'key:generate', description: 'Generate a new application key and set it the .env file')]
final class KeyGenerateCommand extends Command
{
    private const ENV_FILE = BASE_PATH . '/.env';
    private const ENV_EXAMPLE_FILE = BASE_PATH . '/env-example';
    private const KEY_LINE_PREFIX = 'APP_KEY=';

    public function execute(array $args): int
    {
        $this->ensureEnvFilExist();
        $this->generateKey();
        return 0;
    }

    private function ensureEnvFilExist(): int
    {
        if (!file_exists(self::ENV_FILE)) {
            if (!file_exists(self::ENV_EXAMPLE_FILE)) {
                $this->error("Error env-example file not found. Cannot create .env file.");
                return 1;
            }

            if (!copy(self::ENV_EXAMPLE_FILE, self::ENV_FILE)) {
                $this->error("Error: Failed to copy env-example to .env");
                return 1;
            }
            $this->info(".env file created from env-example");
        }
        return 0;
    }

    private function generateKey(): int
    {
        $key = bin2hex(random_bytes(32));

        $envContent = file_get_contents(self::ENV_FILE);
        if ($envContent === false) {
            $this->error("Error: Could not read .env file.");
            return 1;
        }

        $updated = false;
        $lines = explode("\n", $envContent);

        foreach ($lines as &$line) {
            if (str_starts_with($line, self::KEY_LINE_PREFIX)) {
                $line = self::KEY_LINE_PREFIX . $key;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $lines[] = self::KEY_LINE_PREFIX . $key;
        }

        $newEnvContent = implode("\n", $lines);

        if (file_put_contents(self::ENV_FILE, $newEnvContent) === false) {
            $this->error("Error: Failed to write to .env file.");
            return 1;
        }

        $this->info("✅ Application key generated successfully!");
        $this->line("🔑 New application key: {$key}");
        $this->line("🔒 Key has been set in your .env file.");
        return 0;
    }
}
