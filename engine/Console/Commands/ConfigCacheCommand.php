<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\ConfigHashHelper;
use Forge\Core\Traits\OutputHelper;
use Forge\Core\Configuration\Config;

class ConfigCacheCommand implements CommandInterface
{
    use OutputHelper, ConfigHashHelper;

    public function getName(): string
    {
        return 'config:cache';
    }

    public function getDescription(): string
    {
        return 'Cache the framework configuration';
    }

    public function execute(array $args): int
    {
        $baseDir = getcwd();
        $cacheFile = $baseDir . '/storage/framework/config_cache.php';

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }

        try {
            new Config($baseDir, true);
            $this->info("Configuration cached successfully!");
            return 0;
        } catch (\RuntimeException $e) {
            $this->error("Config validation failed: " . $e->getMessage());
            return 1;
        }
    }
}
