<?php
declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'clear:cache', description: 'Clears the application cache')]
class ClearCacheCommand extends Command
{
    use OutputHelper;

    private const CLASS_MAP_CACHE_FILE =
        BASE_PATH . "/storage/framework/cache/class-map.php";
    private const VIEW_CACHE_DIR = BASE_PATH . "/storage/framework/views";


    public function execute(array $args): int
    {
        $this->clearClassMapCache();
        $this->clearViewCache();
        $this->info("Application cache cleared successfully.");
        return 0;
    }

    private function clearClassMapCache(): void
    {
        if (file_exists(self::CLASS_MAP_CACHE_FILE)) {
            if (unlink(self::CLASS_MAP_CACHE_FILE)) {
                $this->success("Class map cache cleared successfully.");
            } else {
                $this->error("Failed to clear class map cache.");
            }
        } else {
            $this->warning("Class map cache file does not exist.");
        }
    }

    private function clearViewCache(): void
    {
        if (is_dir(self::VIEW_CACHE_DIR)) {
            $files = glob(self::VIEW_CACHE_DIR . "/*");
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (!unlink($file)) {
                            $this->error("Failed to clear view cache.");
                            return;
                        }
                    }
                }
                $this->success("View cache cleared successfully.");
            } else {
                $this->warning("View cache directory is empty.");
            }
        } else {
            $this->warning("View cache directory does not exist.");
        }
    }
}
