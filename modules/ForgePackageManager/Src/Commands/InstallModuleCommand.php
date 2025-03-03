<?php

namespace Forge\Modules\ForgePackageManager\Src\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgePackageManager\Src\Services\PackageManager;

class InstallModuleCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'install:module';
    }

    public function getDescription(): string
    {
        return 'Install a module from the registry';
    }

    public function execute(array $args): int
    {
        $this->info("You can bypass the cache by adding force to the end: php forge.php install:module module-name force");
        if (empty($args[0])) {
            $this->error("Module name is required. Usage: php forge.php install:module <module-name>[@version]");
            return 1;
        }

        $moduleNameVersion = $args[0];
        $parts = explode('@', $moduleNameVersion);
        $moduleName = $parts[0];
        $forceCache = $args[1] ?? null;
        $version = $parts[1] ?? null;


        try {
            /** @var PackageManager $packageManager */
            $packageManager = App::getContainer()->get(PackageManager::class);
            $packageManager->installModule($moduleName, $version, $forceCache);
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error installing module: " . $e->getMessage());
            return 1;
        }
    }
}