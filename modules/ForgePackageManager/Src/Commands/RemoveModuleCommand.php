<?php

namespace Forge\Modules\ForgePackageManager\Src\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgePackageManager\Src\Services\PackageManager;

class RemoveModuleCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'remove:module';
    }

    public function getDescription(): string
    {
        return 'Remove an installed module';
    }

    public function execute(array $args): int
    {
        if (empty($args[0])) {
            $this->error("Module name is required. Usage: forge remove:module <module-name>");
            return 1;
        }
        $moduleName = $args[0];

        try {
            /** @var PackageManager $packageManager */
            $packageManager = App::getContainer()->get(PackageManager::class);
            $packageManager->removeModule($moduleName);

            $this->success("Module removed successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}