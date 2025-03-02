<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class ConfigClearCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'config:clear';
    }

    public function getDescription(): string
    {
        return 'Clear the cached configuration';
    }

    public function execute(array $args): int
    {
        $cacheFile = getcwd() . '/storage/framework/config_cache.php';
        $hashFile = getcwd() . '/storage/framework/config_hash.txt';

        $deleted = false;
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $deleted = true;
        }
        if (file_exists($hashFile)) {
            unlink($hashFile);
            $deleted = true;
        }

        if ($deleted) {
            $this->info("Configuration cache cleared!");
            return 0;
        }
        $this->error("No configuration cache found.");
        return 1;
    }
}
